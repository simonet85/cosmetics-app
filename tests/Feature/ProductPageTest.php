<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_price_is_shown_without_a_struck_through_price(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('10,000 FCFA')
            ->assertDontSee('id="product-list-price"', false);
    }

    public function test_discount_price_is_shown_with_the_list_price_struck_through(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSeeInOrder(['id="product-price"', '8,000 FCFA', 'id="product-list-price"', '10,000 FCFA'], false);
    }

    public function test_variant_button_shows_label_and_price_delta(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        ProductVariant::factory()->for($product)->create(['size' => '100ml', 'price_modifier' => 2500]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('100ml')
            ->assertSee('(+2,500 FCFA)')
            ->assertSee('data-price="12500"', false);
    }

    public function test_variant_price_is_based_on_the_discount_price(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000]);
        ProductVariant::factory()->for($product)->create(['price_modifier' => 500]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('data-price="8500"', false);
    }

    public function test_unpublished_products_are_hidden_from_guests_and_customers(): void
    {
        $inactive = Product::factory()->create(['is_active' => false]);
        $archived = Product::factory()->create(['is_archived' => true]);

        foreach ([$inactive, $archived] as $product) {
            $this->get(route('shop.show', $product->slug))->assertNotFound();
            $this->actingAs(User::factory()->create())->get(route('shop.show', $product->slug))->assertNotFound();
            auth()->logout();
        }
    }

    public function test_admins_can_preview_unpublished_products_with_a_banner(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = Product::factory()->create(['is_active' => false]);
        $archived = Product::factory()->create(['is_archived' => true]);

        $this->actingAs($admin)->get(route('shop.show', $inactive->slug))
            ->assertOk()->assertSee('Aperçu administrateur')->assertSee('inactif');

        $this->actingAs($admin)->get(route('shop.show', $archived->slug))
            ->assertOk()->assertSee('Aperçu administrateur')->assertSee('archivé');
    }

    public function test_no_preview_banner_on_a_published_product_even_for_admins(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertDontSee('Aperçu administrateur');
    }

    public function test_admin_list_eye_icon_points_at_the_public_page_for_every_status(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = Product::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('href="'.route('shop.show', $inactive->slug).'" target="_blank"', false);
    }

    public function test_inactive_variants_are_not_offered(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => 'HIDDEN-SIZE', 'is_active' => false]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertDontSee('HIDDEN-SIZE');
    }
}
