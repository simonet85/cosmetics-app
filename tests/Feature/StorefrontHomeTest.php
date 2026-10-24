<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_the_storefront_layout_with_one_h1(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="fr">', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('id="content"', $html);
    }

    public function test_lead_product_is_a_label_with_real_name_price_and_stock(): void
    {
        Product::factory()->create([
            'name' => 'Sérum Éclat',
            'price' => 12500,
            'stock' => 3,
            'is_featured' => true,
            'ingredients' => "Niacinamide, Aloe vera\nGlycérine, Eau",
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Sérum Éclat', $html);
        $this->assertStringContainsString("12\u{00A0}500", $html);
        $this->assertStringContainsString('Plus que 3 en stock', $html);
        $this->assertStringContainsString('Niacinamide', $html);
        $this->assertStringContainsString('Glycérine', $html);
        $this->assertStringNotContainsString('Eau<', $html); // only the first three lines
        $this->assertStringContainsString('data-add-to-cart', $html);
    }

    public function test_discounted_lead_shows_the_old_price_struck_through(): void
    {
        Product::factory()->discounted(9000)->create(['price' => 12000, 'is_featured' => true]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString("9\u{00A0}000", $html);
        $this->assertStringContainsString('<s class', $html);
        $this->assertStringContainsString("12\u{00A0}000", $html);
    }

    public function test_composition_is_hidden_when_the_product_has_no_ingredients(): void
    {
        Product::factory()->create(['is_featured' => true, 'ingredients' => null]);

        $this->get('/')->assertOk()->assertDontSee('Composition');
    }

    public function test_inactive_and_out_of_stock_products_are_not_shown(): void
    {
        Product::factory()->create(['name' => 'Produit caché', 'is_featured' => true, 'is_active' => false]);
        Product::factory()->outOfStock()->create(['name' => 'Produit épuisé', 'is_featured' => true]);

        $this->get('/')->assertOk()->assertDontSee('Produit caché')->assertDontSee('Produit épuisé');
    }

    public function test_empty_shop_still_renders_with_a_way_to_the_shop(): void
    {
        $this->get('/')->assertOk()->assertSee('La sélection arrive bientôt')->assertSee(route('shop.index'));
    }

    public function test_categories_and_testimonials_come_from_the_database_only(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Les rayons')->assertDontSee('Ce que disent nos clientes');

        Category::factory()->create(['name' => 'Soins visage', 'is_active' => true, 'parent_id' => null]);
        Testimonial::create([
            'customer_name' => 'Awa K.', 'comment' => 'Livraison rapide, peau douce.', 'rating' => 5,
            'show_on_home' => true, 'is_active' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Soins visage')->assertSee('Livraison rapide, peau douce.');
    }

    public function test_every_shelf_product_can_be_added_to_the_cart_from_the_home_page(): void
    {
        $lead = Product::factory()->create(['is_featured' => true]);
        $other = Product::factory()->create(['is_best_seller' => true]);

        $html = $this->get('/')->getContent();

        $this->assertSame(2, substr_count($html, 'data-add-to-cart'));

        $this->postJson(route('cart.add'), ['product_id' => $other->id, 'quantity' => 1])
            ->assertOk()->assertJson(['success' => true, 'cart_count' => 1]);
        $this->assertNotSame($lead->id, $other->id);
    }

    private function banner(array $attributes = []): \App\Models\Banner
    {
        return \App\Models\Banner::create(array_merge([
            'type' => 'promotional', 'position' => 'home_top', 'title' => 'Offre de saison', 'subtitle' => 'Jusqu\'à dimanche',
            'image_path' => 'images/hero-slider/hero-slider-15.jpg', 'link_url' => 'https://example.com/offre', 'button_text' => 'En profiter',
            'order' => 1, 'is_active' => true,
        ], $attributes));
    }

    public function test_a_live_banner_is_shown_in_its_slot_with_its_button(): void
    {
        $this->banner();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Offre de saison', $html);
        $this->assertStringContainsString('Jusqu&#039;à dimanche', $html);
        $this->assertStringContainsString('href="https://example.com/offre"', $html);
        $this->assertStringContainsString('En profiter', $html);
        $this->assertStringContainsString('hero-slider-15.jpg', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
        // top banner comes before the lead product
        $this->assertLessThan(strpos($html, 'Les rayons') ?: PHP_INT_MAX, strpos($html, 'Offre de saison'));
    }

    public function test_banners_outside_their_schedule_or_switched_off_are_not_shown(): void
    {
        $this->banner(['title' => 'Éteinte', 'is_active' => false]);
        $this->banner(['title' => 'Terminée', 'start_date' => now()->subDays(9), 'end_date' => now()->subDay()]);
        $this->banner(['title' => 'Pas encore', 'start_date' => now()->addDays(2)]);

        $this->get('/')->assertOk()->assertDontSee('Éteinte')->assertDontSee('Terminée')->assertDontSee('Pas encore');
    }

    public function test_only_one_banner_per_slot_and_the_lowest_order_wins(): void
    {
        $this->banner(['title' => 'Seconde', 'order' => 5]);
        $this->banner(['title' => 'Première', 'order' => 1]);

        $this->get('/')->assertSee('Première')->assertDontSee('Seconde');
    }

    public function test_slots_follow_the_position_and_old_demo_positions_still_land_somewhere(): void
    {
        $this->banner(['title' => 'En haut', 'position' => 'home_top']);
        $this->banner(['title' => 'Au milieu', 'position' => 'home_middle']);
        $this->banner(['title' => 'En bas', 'position' => 'home_bottom']);
        $this->banner(['title' => 'Barre latérale', 'position' => 'sidebar']);

        $html = $this->get('/')->getContent();

        $this->assertLessThan(strpos($html, 'Au milieu'), strpos($html, 'En haut'));
        $this->assertLessThan(strpos($html, 'En bas'), strpos($html, 'Au milieu'));
        $this->assertStringNotContainsString('Barre latérale', $html);

        \App\Models\Banner::query()->delete();
        $this->banner(['title' => 'Ancien hero', 'position' => 'hero', 'type' => 'hero']);
        $this->banner(['title' => 'Ancienne promo', 'position' => 'promo', 'type' => 'promo']);
        $this->get('/')->assertSee('Ancien hero')->assertSee('Ancienne promo');
    }

    public function test_a_banner_without_a_link_has_no_button_and_keeps_title_line_breaks(): void
    {
        $this->banner(['link_url' => null, 'button_text' => null, 'title' => 'Première ligne
Seconde ligne']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('En profiter', $html);
        $this->assertStringContainsString('whitespace-pre-line', $html);
        $this->assertStringContainsString('Première ligne
Seconde ligne', $html);
    }

    public function test_without_banners_the_home_page_is_unchanged(): void
    {
        $this->get('/')->assertOk()->assertDontSee('aria-labelledby="banner-', false);
    }
}
