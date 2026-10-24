<?php

namespace Tests\Feature;

use App\Mail\OrderPaid;
use App\Mail\OrderReceived;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderPayments;
use App\Support\CheckoutToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function checkout(Product $product, string $method = 'cash_on_delivery')
    {
        return $this->withSession(['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]]])
            ->post(route('checkout.process'), [
                'checkout_token' => CheckoutToken::make(),
                'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.com', 'phone' => '0701020304',
                'city' => 'Abidjan', 'quartier' => 'Cocody', 'payment_method' => $method, 'billing_same_as_shipping' => '1',
            ]);
    }

    private function order(array $attributes = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'payment_method' => 'cash_on_delivery', 'payment_status' => 'pending', 'status' => 'pending',
            'subtotal' => 10000, 'tax' => 1500, 'shipping_cost' => 2000, 'total' => 13500,
            'customer_email' => 'awa@example.com',
            'shipping_address' => ['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304'],
        ], $attributes));
        OrderItem::create(['order_id' => $order->id, 'product_id' => Product::factory()->create()->id, 'product_name' => 'Sérum Éclat', 'sku' => 'S1', 'quantity' => 2, 'price' => 5000, 'total' => 10000]);

        return $order;
    }

    public function test_a_cash_order_sends_the_received_mail_with_what_was_ordered(): void
    {
        Mail::fake();
        $product = Product::factory()->create(['name' => 'Crème Karité', 'price' => 6000, 'stock' => 5]);

        $this->checkout($product);

        Mail::assertSent(OrderReceived::class, function (OrderReceived $mail) {
            $html = $mail->render();

            return $mail->hasTo('awa@example.com')
                && str_contains($mail->envelope()->subject, 'est enregistrée')
                && str_contains($html, 'Crème Karité')
                && str_contains($html, "12\u{00A0}000\u{00A0}FCFA")
                && str_contains($html, 'en espèces à la livraison')
                && str_contains($html, '/checkout/success/');
        });
    }

    public function test_the_received_mail_is_not_sent_when_confirmations_are_switched_off(): void
    {
        Mail::fake();
        $this->setting('send_order_confirmation', '0');

        $this->checkout(Product::factory()->create(['stock' => 5]));

        Mail::assertNothingSent();
        $this->assertSame(1, Order::count());
    }

    public function test_a_mobile_money_order_gets_no_receipt_before_it_is_paid(): void
    {
        Mail::fake();

        // The gateway is not configured in tests: the order is rolled back, and still no mail goes out
        $this->checkout(Product::factory()->create(['stock' => 5]), 'moneyfusion');

        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_never_breaks_the_checkout(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->checkout($product);

        $response->assertRedirect(Order::firstOrFail()->successUrl());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_the_paid_mail_follows_the_confirmation_setting_and_has_a_french_subject(): void
    {
        Mail::fake();
        $order = $this->order(['payment_method' => 'moneyfusion']);

        app(OrderPayments::class)->markPaid($order);

        Mail::assertSent(OrderPaid::class, fn (OrderPaid $mail) => str_starts_with($mail->envelope()->subject, 'Confirmation de votre commande'));

        Mail::fake();
        $this->setting('send_order_confirmation', '0');
        $second = $this->order(['payment_method' => 'moneyfusion']);

        app(OrderPayments::class)->markPaid($second);

        Mail::assertNotSent(OrderPaid::class);
        $this->assertSame('paid', $second->fresh()->payment_status);
    }

    public function test_the_paid_mail_shows_the_shipping_cost_in_french_words(): void
    {
        $order = $this->order(['payment_status' => 'paid', 'status' => 'processing']);

        $html = (new OrderPaid($order))->render();

        $this->assertStringContainsString("2\u{00A0}000\u{00A0}FCFA", $html);   // it read a column that is always empty
        $this->assertStringContainsString('Frais de port', $html);
        $this->assertStringContainsString('Payé', $html);
        $this->assertStringNotContainsString('>paid<', $html);
        $this->assertDoesNotMatchRegularExpression('/(January|February|March|April|May|June|July|August|September|October|November|December) \d/', $html);
    }

    public function test_the_admin_order_summary_shows_the_shipping_cost(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.show', $order))
            ->assertOk()->assertSee("2\u{00A0}000\u{00A0}FCFA");
    }

    public function test_the_shipping_notification_follows_its_setting(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $order = $this->order();

        $this->setting('send_shipping_notification', '0');
        $this->actingAs($admin)->put(route('admin.orders.update-status', $order), ['status' => 'shipped'])->assertSessionHasNoErrors();
        Mail::assertNothingSent();

        $second = $this->order();
        $this->setting('send_shipping_notification', '1');
        $this->actingAs($admin)->put(route('admin.orders.update-status', $second), ['status' => 'shipped']);
        Mail::assertSent(\App\Mail\OrderStatusUpdated::class, 1);
    }

    public function test_currency_is_not_a_setting_any_more(): void
    {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Franc CFA (FCFA)', $html);
        $this->assertStringNotContainsString('name="currency"', $html);
        $this->assertStringNotContainsString('>EUR<', $html);
    }
}
