<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_a_product_to_the_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 5]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['success' => true, 'cart_count' => 2]);

        $this->getJson(route('cart.count'))->assertJson(['count' => 2]);
    }

    public function test_adding_the_same_product_twice_merges_quantities(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['cart_count' => 3]);
    }

    public function test_discount_price_is_used_when_present(): void
    {
        $product = Product::factory()->discounted(7500)->create(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])->assertOk();

        $this->assertEquals(7500, app(CartService::class)->lines()[(string) $product->id]['price']);
    }

    public function test_variant_price_is_product_price_plus_modifier(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_modifier' => 2500]);

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk()->assertJson(['cart_count' => 2]);

        $line = app(CartService::class)->lines()[$product->id.'_'.$variant->id];
        $this->assertEquals(12500, $line['price']);
        $this->assertEquals(12500, $variant->price);
    }

    public function test_variant_with_no_modifier_keeps_the_discount_price(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000]);
        $variant = ProductVariant::factory()->for($product)->create(['price_modifier' => 0]);

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->assertEquals(8000, app(CartService::class)->lines()[$product->id.'_'.$variant->id]['price']);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->getJson(route('cart.count'))->assertJson(['count' => 0]);
    }

    public function test_cannot_add_an_unknown_product(): void
    {
        $this->postJson(route('cart.add'), ['product_id' => 999999, 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_quantity_can_be_updated_and_totals_are_returned(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->postJson(route('cart.update'), ['cart_item_id' => (string) $product->id, 'quantity' => 4])
            ->assertOk()
            ->assertJson(['success' => true, 'cart_count' => 4, 'subtotal' => 40000, 'formatted' => ['subtotal' => "40\u{00A0}000\u{00A0}FCFA"]]);
    }

    public function test_update_is_rejected_above_stock(): void
    {
        $product = Product::factory()->create(['stock' => 3]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->postJson(route('cart.update'), ['cart_item_id' => (string) $product->id, 'quantity' => 4])
            ->assertStatus(400);
    }

    public function test_item_can_be_removed(): void
    {
        $product = Product::factory()->create();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->postJson(route('cart.remove'), ['cart_item_id' => (string) $product->id])
            ->assertOk()
            ->assertJson(['cart_count' => 0]);

        $this->postJson(route('cart.remove'), ['cart_item_id' => (string) $product->id])
            ->assertStatus(404);
    }

    public function test_cart_can_be_cleared(): void
    {
        $product = Product::factory()->create();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->postJson(route('cart.clear'))->assertOk()->assertJson(['cart_count' => 0]);
        $this->getJson(route('cart.count'))->assertJson(['count' => 0]);
    }

    public function test_a_variant_of_another_product_cannot_be_added(): void
    {
        $product = Product::factory()->create();
        $foreign = ProductVariant::factory()->create();

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $foreign->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->getJson(route('cart.count'))->assertJson(['count' => 0]);
    }

    public function test_an_inactive_variant_cannot_be_added(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['is_active' => false]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_cannot_add_more_than_the_variant_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 2]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 3])
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_the_session_only_remembers_the_choice_not_the_price(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame(
            ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2],
            session('cart')[(string) $product->id]
        );
    }

    public function test_a_price_change_after_adding_is_reflected_in_the_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock' => 10]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $product->update(['price' => 12000]);

        $this->postJson(route('cart.update'), ['cart_item_id' => (string) $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['subtotal' => 24000, 'item_subtotal' => 24000]);

        $this->get(route('cart.index'))->assertOk()->assertSee("12\u{00A0}000\u{00A0}FCFA");
    }

    public function test_a_new_discount_after_adding_is_reflected_in_the_cart(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $product->update(['discount_price' => 6000]);

        $this->assertEquals(6000, app(CartService::class)->totals()['subtotal']);
    }

    public function test_a_product_deactivated_or_deleted_after_adding_drops_out_of_the_cart(): void
    {
        $kept = Product::factory()->create(['price' => 1000]);
        $deactivated = Product::factory()->create();
        $archived = Product::factory()->create();
        $deleted = Product::factory()->create();

        foreach ([$kept, $deactivated, $archived, $deleted] as $product) {
            $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
        }

        $deactivated->update(['is_active' => false]);
        $archived->update(['is_archived' => true]);
        $deleted->delete();

        $lines = app(CartService::class)->lines();

        $this->assertSame([$kept->id], array_keys($lines));
        $this->assertEquals(1000, app(CartService::class)->totals()['subtotal']);
    }

    public function test_an_unpublished_product_cannot_be_added(): void
    {
        $inactive = Product::factory()->create(['is_active' => false]);

        $this->postJson(route('cart.add'), ['product_id' => $inactive->id, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->getJson(route('cart.count'))->assertJson(['count' => 0]);
    }

    public function test_a_variant_deactivated_after_adding_drops_out_of_the_cart(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 1]);

        $variant->update(['is_active' => false]);

        $this->assertSame([], app(CartService::class)->lines());
    }

    public function test_totals_use_the_tax_and_shipping_settings(): void
    {
        DB::table('settings')->insert([
            ['key' => 'tax_rate', 'value' => '10', 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'standard_shipping_cost', 'value' => '1500', 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $product = Product::factory()->create(['price' => 10000]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $totals = app(CartService::class)->totals();

        $this->assertEquals(10000, $totals['subtotal']);
        $this->assertEquals(1000, $totals['tax']);
        $this->assertEquals(1500, $totals['shipping']);
        $this->assertEquals(12500, $totals['total']);
    }

    public function test_the_total_is_rounded_to_whole_francs(): void
    {
        $product = Product::factory()->create(['price' => 12345]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $totals = app(CartService::class)->totals();

        $this->assertEquals(12345, $totals['subtotal']);
        $this->assertEquals(1851.75, $totals['tax']);
        $this->assertSame(14197.0, $totals['total']);
    }

    public function test_a_fractional_price_still_gives_a_whole_franc_total(): void
    {
        $product = Product::factory()->create(['price' => 29.99]);
        $this->postJson(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->assertSame(34.0, app(CartService::class)->totals()['total']);
    }
}
