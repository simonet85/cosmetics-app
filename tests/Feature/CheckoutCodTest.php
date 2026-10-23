<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutCodTest extends TestCase
{
    use RefreshDatabase;

    /** The session only holds the choice; prices are computed from the database at checkout. */
    private function cartWith(Product $product, int $quantity = 1, ?ProductVariant $variant = null): array
    {
        $key = $product->id.($variant ? '_'.$variant->id : '');

        return ['cart' => [$key => [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
        ]]];
    }

    private function checkoutData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Awa',
            'last_name' => 'Koné',
            'email' => 'awa@example.com',
            'phone' => '0701020304',
            'city' => 'Abidjan',
            'quartier' => 'Cocody',
            'payment_method' => 'cash_on_delivery',
            'billing_same_as_shipping' => '1',
        ], $overrides);
    }

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->insert([
            'key' => $key,
            'value' => $value,
            'type' => 'text',
            'group' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_cod_checkout_creates_an_order_and_redirects_to_success(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);

        $response = $this->withSession($this->cartWith($product, 2))
            ->post(route('checkout.process'), $this->checkoutData(['notes' => 'Appeler avant']));

        $order = Order::firstOrFail();
        $response->assertRedirect($order->successUrl());

        // 20 000 subtotal + 15 % default tax, free standard shipping by default
        $this->assertNull($order->user_id);
        $this->assertSame('cash_on_delivery', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertEquals(20000, $order->subtotal);
        $this->assertEquals(3000, $order->tax);
        $this->assertEquals(23000, $order->total);
        $this->assertSame('awa@example.com', $order->customer_email);
        $this->assertSame('0701020304', $order->customer_phone);
        $this->assertSame('Appeler avant', $order->notes);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_order_items_are_created_and_stock_is_decremented(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5, 'sku' => 'SKU-A']);

        $this->withSession($this->cartWith($product, 2))->post(route('checkout.process'), $this->checkoutData());

        $item = Order::firstOrFail()->items()->firstOrFail();
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame('SKU-A', $item->sku);
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(10000, $item->price);
        $this->assertEquals(20000, $item->total);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_the_cart_is_cleared_after_a_cod_order(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData());

        $this->assertNull(session('cart'));
        $this->getJson(route('cart.count'))->assertJson(['count' => 0]);
    }

    public function test_the_addresses_are_stored_as_plain_json_objects(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $shipping = $order->shipping_address;
        $billing = $order->billing_address;

        $this->assertEquals(['first_name' => 'Awa', 'last_name' => 'Koné', 'city' => 'Abidjan', 'quartier' => 'Cocody', 'phone' => '0701020304'], $shipping);
        $this->assertEquals($shipping, $billing);

        // Plain JSON object in the column, not a JSON string containing JSON.
        $raw = DB::table('orders')->where('id', $order->id)->value('shipping_address');
        $this->assertIsArray(json_decode($raw, true));
    }

    public function test_a_different_billing_address_is_stored_separately(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData([
            'billing_same_as_shipping' => null,
            'billing_first_name' => 'Moussa',
            'billing_last_name' => 'Traoré',
            'billing_city' => 'Bouaké',
            'billing_quartier' => 'Koko',
        ]));

        $billing = Order::firstOrFail()->billing_address;
        $this->assertSame('Moussa', $billing['first_name']);
        $this->assertSame('Bouaké', $billing['city']);
    }

    public function test_a_logged_in_customer_owns_the_order_and_sees_the_success_page(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame($user->id, $order->user_id);

        $this->actingAs($user)->get(route('checkout.success', $order->id))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_a_customer_cannot_see_someone_elses_success_page(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('checkout.success', $order->id))
            ->assertForbidden();
    }

    public function test_shipping_cost_is_added_below_the_free_shipping_threshold(): void
    {
        $this->setting('standard_shipping_cost', '2000');
        $this->setting('free_shipping_threshold', '50000');
        $product = Product::factory()->create(['price' => 10000]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $this->assertEquals(2000, $order->shipping_cost);
        $this->assertEquals(10000 + 1500 + 2000, $order->total);
    }

    public function test_shipping_is_free_at_or_above_the_threshold(): void
    {
        $this->setting('standard_shipping_cost', '2000');
        $this->setting('free_shipping_threshold', '10000');
        $product = Product::factory()->create(['price' => 10000]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $this->assertEquals(0, Order::firstOrFail()->shipping_cost);
    }

    public function test_the_tax_rate_comes_from_the_settings(): void
    {
        $this->setting('tax_rate', '0');
        $product = Product::factory()->create(['price' => 10000]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $this->assertEquals(0, $order->tax);
        $this->assertEquals(10000, $order->total);
    }

    public function test_a_variant_is_recorded_on_the_order_item(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_modifier' => 2500]);

        $this->withSession($this->cartWith($product, 1, $variant))
            ->post(route('checkout.process'), $this->checkoutData());

        $item = Order::firstOrFail()->items()->firstOrFail();
        $this->assertSame($variant->id, $item->variant_id);
        $this->assertEquals(12500, $item->price);
    }

    public function test_checkout_with_an_empty_cart_goes_back_to_the_cart(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));

        $this->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_the_checkout_page_renders_with_a_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->withSession($this->cartWith($product, 2))
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_required_fields_are_validated(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'city', 'quartier', 'payment_method']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_unknown_payment_method_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData(['payment_method' => 'bitcoin']))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_billing_fields_are_required_when_not_the_same_as_shipping(): void
    {
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData(['billing_same_as_shipping' => null]))
            ->assertSessionHasErrors(['billing_first_name', 'billing_last_name', 'billing_city', 'billing_quartier']);
    }

    public function test_an_order_is_rolled_back_when_stock_ran_out_after_adding_to_the_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $session = $this->cartWith($product, 3);
        $product->update(['stock' => 1]);

        $this->withSession($session)
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_a_partial_failure_rolls_back_every_line(): void
    {
        $ok = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $short = Product::factory()->create(['price' => 5000, 'stock' => 5]);
        $cart = $this->cartWith($ok, 2)['cart'] + $this->cartWith($short, 4)['cart'];
        $short->update(['stock' => 1]);

        $this->withSession(['cart' => $cart])
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $ok->fresh()->stock, 'stock decremented for the first line must be rolled back');
    }

    public function test_a_guest_is_redirected_to_a_signed_link_that_opens_the_confirmation_page(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->withSession($this->cartWith($product))->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();

        $this->get($order->successUrl())->assertOk()->assertSee($order->order_number);
    }

    public function test_a_guest_order_confirmation_is_not_readable_without_the_signature(): void
    {
        $order = Order::factory()->create(['user_id' => null]);

        $this->get(route('checkout.success', $order->id))->assertForbidden();
    }

    public function test_a_tampered_signed_link_is_rejected(): void
    {
        $order = Order::factory()->create(['user_id' => null]);
        $other = Order::factory()->create(['user_id' => null]);

        // Reuse order A's signature on order B's URL.
        $signature = parse_url($order->successUrl(), PHP_URL_QUERY);

        $this->get(route('checkout.success', $other->id).'?'.$signature)->assertForbidden();
    }

    public function test_a_logged_in_owner_does_not_need_the_signature(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('checkout.success', $order->id))->assertOk();
    }

    public function test_a_payment_method_switched_off_in_the_settings_is_refused(): void
    {
        $this->setting('payment_cash_on_delivery_enabled', '0');
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData(['payment_method' => 'cash_on_delivery']))
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_other_enabled_methods_still_work_when_one_is_switched_off(): void
    {
        $this->setting('payment_moneyfusion_enabled', '0');
        $product = Product::factory()->create();

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData(['payment_method' => 'bank_transfer']))
            ->assertSessionHasNoErrors();

        $this->assertSame('bank_transfer', Order::firstOrFail()->payment_method);
    }

    public function test_variant_stock_is_decremented_with_the_product_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 4]);

        $this->withSession($this->cartWith($product, 3, $variant))
            ->post(route('checkout.process'), $this->checkoutData());

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->stock);
    }

    public function test_checkout_is_rolled_back_when_the_variant_is_out_of_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 5]);
        $session = $this->cartWith($product, 3, $variant);
        $variant->update(['stock' => 1]);

        $this->withSession($session)
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(1, $variant->fresh()->stock);
    }

    public function test_checkout_refuses_a_variant_that_belongs_to_another_product_or_is_inactive(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $foreign = ProductVariant::factory()->create(['stock' => 10]);
        $inactive = ProductVariant::factory()->for($product)->create(['stock' => 10, 'is_active' => false]);

        foreach ([$foreign, $inactive] as $variant) {
            $this->withSession($this->cartWith($product, 1, $variant))
                ->post(route('checkout.process'), $this->checkoutData())
                ->assertSessionHas('error');
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_non_moneyfusion_checkout_works_when_the_gateway_is_not_configured(): void
    {
        config(['moneyfusion.app_key' => null]);
        $product = Product::factory()->create();

        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));

        $this->withSession($this->cartWith($product))
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->get($order->successUrl())->assertOk();
    }

    public function test_choosing_moneyfusion_without_configuration_fails_gracefully_and_rolls_back(): void
    {
        config(['moneyfusion.app_key' => null]);
        $product = Product::factory()->create(['stock' => 5]);

        $this->withSession($this->cartWith($product, 2))
            ->post(route('checkout.process'), $this->checkoutData(['payment_method' => 'moneyfusion']))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_checkout_charges_the_current_price_not_the_price_at_add_to_cart_time(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $session = $this->cartWith($product, 2);

        $product->update(['price' => 15000]);

        $this->withSession($session)->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $this->assertEquals(30000, $order->subtotal);
        $this->assertEquals(15000, $order->items()->firstOrFail()->price);
    }

    public function test_a_stale_price_left_in_an_old_session_is_ignored(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        // Carts created before prices were dropped from the session still carry one.
        $this->withSession(['cart' => [(string) $product->id => ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1, 'price' => 1]]])
            ->post(route('checkout.process'), $this->checkoutData());

        $this->assertEquals(10000, Order::firstOrFail()->subtotal);
    }

    public function test_a_discount_removed_after_adding_is_charged_at_the_full_price(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000]);
        $session = $this->cartWith($product);

        $product->update(['discount_price' => null]);

        $this->withSession($session)->post(route('checkout.process'), $this->checkoutData());

        $this->assertEquals(10000, Order::firstOrFail()->subtotal);
    }

    public function test_a_product_unpublished_since_adding_is_not_ordered(): void
    {
        $product = Product::factory()->create();
        $session = $this->cartWith($product);

        $product->update(['is_active' => false]);

        $this->withSession($session)
            ->post(route('checkout.process'), $this->checkoutData())
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_only_still_available_lines_are_ordered(): void
    {
        $kept = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $gone = Product::factory()->create(['price' => 5000, 'stock' => 5]);
        $cart = $this->cartWith($kept, 1)['cart'] + $this->cartWith($gone, 1)['cart'];

        $gone->delete();

        $this->withSession(['cart' => $cart])->post(route('checkout.process'), $this->checkoutData());

        $order = Order::firstOrFail();
        $this->assertCount(1, $order->items);
        $this->assertEquals(10000, $order->subtotal);
    }
}
