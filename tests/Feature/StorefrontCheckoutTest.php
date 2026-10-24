<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\CheckoutToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cartWith(Product $product, int $quantity = 1): array
    {
        return ['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => $quantity]]];
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'checkout_token' => CheckoutToken::make(),
            'first_name' => 'Awa', 'last_name' => 'Koné', 'email' => 'awa@example.com',
            'phone' => '0701020304', 'city' => 'Abidjan', 'quartier' => 'Cocody',
            'payment_method' => 'cash_on_delivery', 'billing_same_as_shipping' => '1',
        ], $overrides);
    }

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_checkout_page_is_the_storefront_one_with_a_token_and_labelled_fields(): void
    {
        $product = Product::factory()->create(['name' => 'Sérum Éclat', 'price' => 12000, 'stock' => 5]);

        $html = $this->withSession($this->cartWith($product, 2))->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertMatchesRegularExpression('/name="checkout_token" value="[A-Za-z0-9]{32}\.\d+\.[a-f0-9]{64}"/', $html);
        foreach (['first_name', 'last_name', 'email', 'phone', 'city', 'quartier'] as $field) {
            $this->assertStringContainsString('for="field-'.$field.'"', $html);
        }
        $this->assertStringContainsString('autocomplete="email"', $html);
        $this->assertStringContainsString('autocomplete="tel"', $html);
        $this->assertStringContainsString('Sérum Éclat', $html);
        $this->assertStringContainsString("24\u{00A0}000\u{00A0}FCFA", $html);
        $this->assertStringContainsString('data-submit', $html);
    }

    public function test_no_payment_method_is_preselected_when_several_are_offered(): void
    {
        $html = $this->withSession($this->cartWith(Product::factory()->create()))->get(route('checkout.index'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="payment_method"[^>]*\schecked/', $html);
        $this->assertStringContainsString('Mobile money', $html);
    }

    public function test_a_single_enabled_payment_method_is_preselected(): void
    {
        foreach (['credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'] as $method) {
            $this->setting("payment_{$method}_enabled", '0');
        }

        $this->withSession($this->cartWith(Product::factory()->create()))->get(route('checkout.index'))
            ->assertOk()->assertSee('value="moneyfusion"', false)
            ->assertSee('checked', false);
    }

    public function test_a_signed_in_customer_gets_the_form_prefilled(): void
    {
        $user = User::factory()->create(['first_name' => 'Fanta', 'last_name' => 'Diallo', 'email' => 'fanta@example.com']);

        $this->actingAs($user)->withSession($this->cartWith(Product::factory()->create()))
            ->get(route('checkout.index'))->assertSee('value="Fanta"', false)->assertSee('value="fanta@example.com"', false);
    }

    public function test_billing_fields_exist_and_show_when_the_box_is_unchecked(): void
    {
        $html = $this->withSession($this->cartWith(Product::factory()->create()))->get(route('checkout.index'))->getContent();

        $this->assertStringContainsString('name="billing_city"', $html);
        $this->assertStringContainsString('peer-[:not(:checked)]:grid', $html);
    }

    public function test_the_same_token_sent_twice_creates_one_order_and_takes_stock_once(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $data = $this->data();

        $first = $this->withSession($this->cartWith($product, 2))->post(route('checkout.process'), $data);
        $order = Order::firstOrFail();
        $first->assertRedirect($order->successUrl());

        // The cart was emptied by the first order; the replay still lands on the same order
        $second = $this->post(route('checkout.process'), $data);

        $second->assertRedirect($order->successUrl());
        $this->assertSame(1, Order::count());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_a_replay_with_a_full_cart_still_creates_no_second_order(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $data = $this->data();

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $data);
        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $data);

        $this->assertSame(1, Order::count());
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_a_request_that_arrives_while_the_first_one_is_running_is_refused(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $data = $this->data();
        $lock = Cache::lock('checkout:'.hash('sha256', $data['checkout_token']).':lock', 60);
        $this->assertTrue($lock->get());

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $data)
            ->assertSessionHas('error');

        $this->assertSame(0, Order::count());
        $this->assertSame(5, $product->fresh()->stock);
        $lock->release();
    }

    public function test_a_failed_attempt_leaves_the_token_usable_for_a_retry(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $data = $this->data();

        $this->withSession($this->cartWith($product, 3))->post(route('checkout.process'), $data)
            ->assertSessionHas('error');
        $this->assertSame(0, Order::count());

        $product->update(['stock' => 5]);
        $this->withSession($this->cartWith($product, 3))->post(route('checkout.process'), $data);

        $this->assertSame(1, Order::count());
    }

    public function test_missing_forged_or_expired_tokens_place_no_order(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $session = $this->cartWith($product);

        $this->withSession($session)->post(route('checkout.process'), array_diff_key($this->data(), ['checkout_token' => 1]))
            ->assertSessionHasErrors('checkout_token');

        $this->withSession($session)->post(route('checkout.process'), $this->data(['checkout_token' => 'abc.123.def']))
            ->assertRedirect(route('checkout.index'))->assertSessionHas('error');

        $this->travel(2)->days();
        $old = CheckoutToken::make();
        $this->travel(2)->days();
        $this->withSession($session)->post(route('checkout.process'), $this->data(['checkout_token' => $old]))
            ->assertRedirect(route('checkout.index'));

        $this->assertSame(0, Order::count());
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_a_token_signed_with_another_key_is_rejected(): void
    {
        $token = CheckoutToken::make();
        [$id, $time] = explode('.', $token);

        $this->assertTrue(CheckoutToken::valid($token));
        $this->assertFalse(CheckoutToken::valid("{$id}.{$time}.".str_repeat('0', 64)));
        $this->assertFalse(CheckoutToken::valid("{$id}x.{$time}.".explode('.', $token)[2]));
    }

    public function test_internal_errors_are_never_shown_to_the_customer(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        $this->setting('payment_moneyfusion_enabled', '1');

        // MoneyFusion is not configured in tests: the failure must be reported, not echoed
        $response = $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->data(['payment_method' => 'moneyfusion']));

        $response->assertSessionHas('error');
        $message = session('error');
        $this->assertStringNotContainsString('Exception', $message);
        $this->assertStringContainsString('Votre panier est conservé', $message);
        $this->assertSame(0, Order::count());
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_the_error_summary_links_each_message_to_its_field(): void
    {
        $product = Product::factory()->create();

        $html = $this->withSession($this->cartWith($product))
            ->from(route('checkout.index'))
            ->followingRedirects()
            ->post(route('checkout.process'), ['checkout_token' => CheckoutToken::make()])
            ->getContent();

        $this->assertStringContainsString('data-error-summary', $html);
        $this->assertStringContainsString('href="#field-first_name"', $html);
        $this->assertStringContainsString('Le prénom est obligatoire.', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    public function test_empty_carts_cannot_reach_the_checkout(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));
    }
}
