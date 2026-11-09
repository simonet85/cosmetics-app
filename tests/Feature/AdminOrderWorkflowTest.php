<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Mail\OrderStatusUpdated;
use App\Mail\PaymentFailed;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @return array{0: Order, 1: Product} */
    private function order(array $attributes = [], int $quantity = 1): array
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create($attributes);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => null,
            'product_name' => 'Sérum Éclat', 'sku' => 'S1', 'quantity' => $quantity,
            'price' => 1000, 'total' => 1000 * $quantity,
        ]);

        return [$order, $product];
    }

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_marking_the_payment_paid_sends_the_confirmation_with_the_invoice_and_starts_processing(): void
    {
        Mail::fake();
        [$order] = $this->order(['payment_status' => 'pending', 'status' => 'pending']);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-payment-status', $order), ['payment_status' => 'paid'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('processing', $order->status);
        Mail::assertSent(OrderPaid::class, fn (OrderPaid $mail) => $mail->hasTo($order->customer_email));
    }

    public function test_a_paid_order_cannot_be_marked_failed(): void
    {
        Mail::fake();
        [$order] = $this->order(['payment_status' => 'paid', 'status' => 'processing']);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-payment-status', $order), ['payment_status' => 'failed'])
            ->assertSessionHas('error');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_marking_a_pending_payment_failed_cancels_the_order_and_gives_the_stock_back(): void
    {
        Mail::fake();
        [$order, $product] = $this->order(['payment_status' => 'pending', 'status' => 'pending'], 2);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-payment-status', $order), ['payment_status' => 'failed'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(12, $product->fresh()->stock);
        Mail::assertSent(PaymentFailed::class);
    }

    public function test_a_refund_only_changes_the_payment_record(): void
    {
        [$order, $product] = $this->order(['payment_status' => 'paid', 'status' => 'processing'], 2);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-payment-status', $order), ['payment_status' => 'refunded'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('processing', $order->status);
        $this->assertNull($order->stock_released_at);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_the_shipping_mail_follows_the_setting(): void
    {
        Mail::fake();
        $this->setting('send_shipping_notification', '0');
        [$order] = $this->order(['status' => 'processing']);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-status', $order), ['status' => 'shipped'])
            ->assertSessionHas('success');

        $this->assertSame('shipped', $order->fresh()->status);
        Mail::assertNotSent(OrderStatusUpdated::class);
    }

    public function test_the_tracking_can_be_edited_on_its_own_and_cleared(): void
    {
        Mail::fake();
        [$order] = $this->order(['status' => 'processing']);

        $this->actingAs($this->admin())->put(route('admin.orders.update-status', $order), [
            'status' => 'shipped',
            'shipping_carrier' => 'DHL',
            'tracking_number' => 'TRACK-42',
            'tracking_url' => 'https://track.example/42',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('DHL', $order->shipping_carrier);
        $this->assertSame('TRACK-42', $order->tracking_number);
        $this->assertSame('https://track.example/42', $order->tracking_url);

        Mail::fake();
        $this->actingAs($this->admin())->put(route('admin.orders.update-status', $order), [
            'status' => 'shipped',
            'shipping_carrier' => '',
            'tracking_number' => '',
            'tracking_url' => '',
        ])->assertSessionHas('success');

        $order->refresh();
        $this->assertNull($order->shipping_carrier);
        $this->assertNull($order->tracking_number);
        $this->assertNull($order->tracking_url);
        Mail::assertNothingSent();
    }

    public function test_a_status_change_alone_keeps_the_tracking(): void
    {
        [$order] = $this->order([
            'status' => 'shipped', 'shipping_carrier' => 'DHL', 'tracking_number' => 'TRACK-42',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.orders.update-status', $order), ['status' => 'delivered'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame('DHL', $order->shipping_carrier);
        $this->assertSame('TRACK-42', $order->tracking_number);
    }

    public function test_an_invalid_tracking_link_is_rejected(): void
    {
        [$order] = $this->order();

        $this->actingAs($this->admin())->put(route('admin.orders.update-status', $order), [
            'status' => 'processing',
            'tracking_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('tracking_url');
    }
}
