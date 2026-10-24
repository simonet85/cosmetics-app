<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Tests\TestCase;

class MoneyFusionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cartWith(Product $product, int $quantity = 1): array
    {
        return ['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => $quantity]]];
    }

    private function checkoutData(): array
    {
        return [
            'checkout_token' => \App\Support\CheckoutToken::make(),
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'email' => 'awa@example.com',
            'phone' => '0701020304',
            'city' => 'Abidjan',
            'quartier' => 'Cocody',
            'payment_method' => 'moneyfusion',
            'billing_same_as_shipping' => '1',
        ];
    }

    public function test_choosing_moneyfusion_creates_the_order_and_payment_then_redirects_to_the_gateway(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response([
            'statut' => true,
            'token' => 'tok_abc',
            'url' => 'https://pay.moneyfusion.test/checkout/tok_abc',
            'message' => 'ok',
        ])]);
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);

        $this->withSession($this->cartWith($product, 2))
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect('https://pay.moneyfusion.test/checkout/tok_abc');

        $order = Order::firstOrFail();
        $this->assertSame('moneyfusion', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('tok_abc', $order->moneyfusion_token);
        $this->assertSame('https://pay.moneyfusion.test/checkout/tok_abc', $order->moneyfusion_payment_url);
        $this->assertSame(3, $product->fresh()->stock);

        $payment = MoneyFusionPayment::where('token_pay', 'tok_abc')->firstOrFail();
        $this->assertSame($order->id, $payment->order_id);
        $this->assertEquals($order->total, $payment->montant);
        $this->assertSame('pending', $payment->statut);

        // The cart is kept until the payment is confirmed
        $this->assertNotEmpty(session('cart'));
    }

    public function test_the_gateway_is_sent_the_server_side_price_the_webhook_and_the_return_url(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response(['statut' => true, 'token' => 't', 'url' => 'https://pay.moneyfusion.test/t'])]);
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5, 'name' => 'Sérum']);

        $this->withSession($this->cartWith($product, 2))->post(route('checkout.process'), $this->checkoutData());

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://moneyfusion.test/pay'
                && (string) $body['totalPrice'] === '23000'
                && $body['nomclient'] === 'Awa Koné'
                && isset($body['webhook_url'], $body['return_url'])
                && $body['article'] === [['nom' => 'Sérum', 'montant' => 10000, 'quantite' => 2]];
        });
    }

    public function test_a_gateway_error_cancels_the_order_and_puts_the_stock_back(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response('gateway down', 500)]);
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);

        $this->withSession($this->cartWith($product, 2))
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('moneyfusion_payments', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_a_gateway_answer_without_a_payment_url_is_treated_as_a_failure(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response(['statut' => true, 'token' => 'tok_only'])]);
        $product = Product::factory()->create(['stock' => 5]);

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_the_amount_sent_to_the_gateway_and_stored_is_the_whole_franc_order_total(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response(['statut' => true, 'token' => 'tok_r', 'url' => 'https://pay.moneyfusion.test/tok_r'])]);
        // 12 345 + 15 % tax = 14 196.75 -> 14 197 F
        $product = Product::factory()->create(['price' => 12345, 'stock' => 5]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $this->assertEquals(14197, $order->total);
        $this->assertEquals(14197, MoneyFusionPayment::firstOrFail()->montant);
        Http::assertSent(fn ($request) => (string) $request->data()['totalPrice'] === '14197');
    }

    public function test_the_return_url_sent_to_the_gateway_names_the_order(): void
    {
        Http::fake(['moneyfusion.test/pay' => Http::response(['statut' => true, 'token' => 'tok_o', 'url' => 'https://pay.moneyfusion.test/tok_o'])]);
        $product = Product::factory()->create(['stock' => 5]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        Http::assertSent(fn ($request) => $request->data()['return_url'] === route('payment.callback', ['order' => $order->id]));
    }
}
