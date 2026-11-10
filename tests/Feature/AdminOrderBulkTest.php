<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminOrderBulkTest extends TestCase
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

        \Illuminate\Support\Facades\Cache::forget('settings');
    }

    public function test_the_bulk_actions_are_for_admins_only(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.orders.bulk-status'), ['ids' => [$order->id], 'status' => 'shipped'])
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.orders.bulk-check-payments'), ['ids' => [$order->id]])
            ->assertForbidden();
    }

    public function test_a_bulk_status_change_applies_to_every_selected_order_and_notifies_the_customers(): void
    {
        Mail::fake();
        [$first] = $this->order();
        [$second] = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-status'), ['ids' => [$first->id, $second->id], 'status' => 'shipped'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '2 commande(s) mise(s) à jour'));

        $this->assertSame('shipped', $first->fresh()->status);
        $this->assertSame('shipped', $second->fresh()->status);
        Mail::assertSent(OrderStatusUpdated::class, 2);
    }

    public function test_cancelling_in_bulk_gives_the_stock_back(): void
    {
        [$order, $product] = $this->order([], 2);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-status'), ['ids' => [$order->id], 'status' => 'cancelled'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(12, $product->fresh()->stock);
    }

    public function test_a_selection_larger_than_twenty_five_is_rejected(): void
    {
        $ids = Order::factory()->count(26)->create()->pluck('id')->all();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-status'), ['ids' => $ids, 'status' => 'processing'])
            ->assertSessionHasErrors('ids');
    }

    public function test_unknown_ids_are_reported_and_do_not_stop_the_others(): void
    {
        [$order] = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-status'), ['ids' => [$order->id, 999999], 'status' => 'processing'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 commande(s) mise(s) à jour')
                && str_contains($message, '1 ignorée'));

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_bulk_check_only_asks_about_moneyfusion_payments_that_are_not_paid(): void
    {
        $this->setting('moneyfusion_bulk_check_pause_ms', '0');
        $this->mock(PaymentVerifier::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyOrder')->once()->andReturn(PaymentVerifier::PENDING);
        });

        [$pending] = $this->order(['payment_method' => 'moneyfusion', 'payment_status' => 'pending']);
        [$paid] = $this->order(['payment_method' => 'moneyfusion', 'payment_status' => 'paid']);
        [$cod] = $this->order(['payment_method' => 'cash_on_delivery', 'payment_status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-check-payments'), ['ids' => [$pending->id, $paid->id, $cod->id, 999999]])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 paiement(s) vérifié(s)')
                && str_contains($message, '1 toujours en attente'));
    }

    public function test_bulk_check_without_any_moneyfusion_payment_says_so(): void
    {
        [$cod] = $this->order(['payment_method' => 'cash_on_delivery', 'payment_status' => 'pending']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.bulk-check-payments'), ['ids' => [$cod->id]])
            ->assertSessionHas('error');
    }
}
