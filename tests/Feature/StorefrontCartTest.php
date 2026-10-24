<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontCartTest extends TestCase
{
    use RefreshDatabase;

    private function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null)
    {
        return $this->postJson(route('cart.add'), array_filter([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'variant_id' => $variant?->id,
        ]));
    }

    public function test_empty_cart_offers_the_shop(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()->assertSee('Votre panier est vide')->assertSee(route('shop.index'));
    }

    public function test_cart_page_shows_lines_totals_and_the_free_shipping_gap(): void
    {
        $product = Product::factory()->create(['name' => 'Sérum Éclat', 'price' => 12000, 'stock' => 10]);
        $this->add($product, 2);

        $html = $this->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Sérum Éclat', $html);
        $this->assertStringContainsString("12\u{00A0}000\u{00A0}FCFA l'unité", $html);
        $this->assertStringContainsString("24\u{00A0}000\u{00A0}FCFA", $html);
        $this->assertStringContainsString("Plus que 26\u{00A0}000\u{00A0}FCFA pour la livraison gratuite.", $html);
        $this->assertStringContainsString('Passer la commande', $html);
        $this->assertStringContainsString('Taxes (15 %)', $html);
    }

    public function test_free_shipping_is_announced_once_the_threshold_is_reached(): void
    {
        $this->add(Product::factory()->create(['price' => 60000, 'stock' => 5]));

        $this->get(route('cart.index'))
            ->assertSee('La livraison est gratuite pour cette commande.')->assertSee('Gratuite');
    }

    public function test_update_and_remove_return_formatted_whole_franc_totals(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->add($product);

        $this->postJson(route('cart.update'), ['cart_item_id' => (string) $product->id, 'quantity' => 3])
            ->assertOk()
            ->assertJsonPath('formatted.item_subtotal', "30\u{00A0}000\u{00A0}FCFA")
            ->assertJsonPath('formatted.total', "34\u{00A0}500\u{00A0}FCFA")
            ->assertJsonPath('free_shipping_remaining', 20000);

        $this->postJson(route('cart.remove'), ['cart_item_id' => (string) $product->id])
            ->assertOk()->assertJsonPath('cart_count', 0)->assertJsonPath('formatted.shipping', 'Gratuite');
    }

    public function test_a_product_with_options_cannot_be_added_without_choosing_one(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 5]);

        $this->add($product)->assertStatus(422)->assertJsonPath('success', false);
        $this->add($product, 1, $variant)->assertOk();
    }

    public function test_quantity_cannot_exceed_the_variant_stock_when_updating_or_merging(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 3]);
        $this->add($product, 2, $variant)->assertOk();
        $key = $product->id.'_'.$variant->id;

        $this->postJson(route('cart.update'), ['cart_item_id' => $key, 'quantity' => 4])->assertStatus(400);
        $this->add($product, 2, $variant)->assertStatus(400);
        $this->postJson(route('cart.update'), ['cart_item_id' => $key, 'quantity' => 3])->assertOk();
    }

    public function test_a_signed_in_customer_keeps_the_cart_in_the_database(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->actingAs($user);
        $this->add($product, 2);
        $this->assertSame(2, (int) Cart::where('user_id', $user->id)->sum('quantity'));

        $this->postJson(route('cart.update'), ['cart_item_id' => (string) $product->id, 'quantity' => 5]);
        $this->assertSame(5, (int) Cart::where('user_id', $user->id)->sum('quantity'));

        $this->postJson(route('cart.clear'));
        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
    }

    public function test_guests_never_write_to_the_cart_table(): void
    {
        $this->add(Product::factory()->create(['stock' => 10]));

        $this->assertSame(0, Cart::count());
    }

    public function test_signing_in_brings_back_the_saved_cart_and_merges_the_browser_cart(): void
    {
        $user = User::factory()->create(['email' => 'client@example.com', 'password' => bcrypt('secret-pass-1')]);
        $kept = Product::factory()->create(['stock' => 10]);
        $both = Product::factory()->create(['stock' => 10]);
        $browserOnly = Product::factory()->create(['stock' => 10]);

        Cart::create(['user_id' => $user->id, 'product_id' => $kept->id, 'quantity' => 2]);
        Cart::create(['user_id' => $user->id, 'product_id' => $both->id, 'quantity' => 1]);

        $this->add($both, 3);
        $this->add($browserOnly, 1);

        $this->post(route('login.post'), ['email' => 'client@example.com', 'password' => 'secret-pass-1'])->assertRedirect();

        $cart = session('cart');
        $this->assertSame(2, $cart[(string) $kept->id]['quantity']);
        $this->assertSame(3, $cart[(string) $both->id]['quantity']);      // larger of the two, not 1 + 3
        $this->assertSame(1, $cart[(string) $browserOnly->id]['quantity']);
        $this->assertSame(6, (int) Cart::where('user_id', $user->id)->sum('quantity'));
    }

    public function test_merging_drops_gone_products_and_caps_quantities_to_the_stock(): void
    {
        $user = User::factory()->create(['email' => 'client2@example.com', 'password' => bcrypt('secret-pass-1')]);
        $low = Product::factory()->create(['stock' => 2]);
        $inactive = Product::factory()->create(['stock' => 5, 'is_active' => false]);
        $soldOut = Product::factory()->create(['stock' => 0]);

        foreach ([$low, $inactive, $soldOut] as $product) {
            Cart::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 9]);
        }

        $this->post(route('login.post'), ['email' => 'client2@example.com', 'password' => 'secret-pass-1']);

        $this->assertSame([(string) $low->id => 2], collect(session('cart'))->map(fn ($item) => $item['quantity'])->all());
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
    }

    public function test_the_saved_cart_survives_logout(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $this->actingAs($user);
        $this->add($product, 2);
        $this->post(route('logout'));

        $this->assertSame(2, (int) Cart::where('user_id', $user->id)->sum('quantity'));
    }
}
