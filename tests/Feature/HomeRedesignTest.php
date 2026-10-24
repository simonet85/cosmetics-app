<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function rayon(string $name, int $products = 1): Category
    {
        $category = Category::factory()->create(['name' => $name, 'is_active' => true, 'parent_id' => null]);
        Product::factory()->count($products)->create(['is_active' => true, 'is_archived' => false, 'stock' => 5])
            ->each(fn (Product $product) => $product->categories()->attach($category));

        return $category;
    }

    public function test_the_trust_strip_reads_delivery_and_payments_from_the_settings(): void
    {
        $this->setting('standard_shipping_cost', '2000');
        $this->setting('free_shipping_threshold', '30000');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Nos engagements"', $html);
        $this->assertStringContainsString("Livraison gratuite dès 30\u{00A0}000\u{00A0}FCFA", $html);
        $this->assertStringContainsString('Commandez sans créer de compte', $html);
    }

    public function test_a_rayon_is_a_tile_with_the_photo_of_a_product_on_sale(): void
    {
        $category = $this->rayon('Soins visage');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-labelledby="univers"', $html);
        $this->assertStringContainsString(route('shop.category', $category->slug), $html);
    }

    public function test_a_rayon_with_nothing_on_sale_is_not_offered(): void
    {
        $empty = Category::factory()->create(['name' => 'Parfums vides', 'is_active' => true, 'parent_id' => null]);
        $this->rayon('Soins visage');

        $html = $this->get('/')->assertOk()->getContent();
        $page = substr($html, strpos($html, 'aria-labelledby="univers"'));
        $page = substr($page, 0, strpos($page, '</section>'));

        $this->assertStringNotContainsString(route('shop.category', $empty->slug), $page);
        $this->assertStringContainsString('Soins visage', $page);
    }

    public function test_the_selection_is_a_grid_of_product_cards_with_an_add_button(): void
    {
        // three of them open the slider, the others are left for the shelf
        Product::factory()->count(5)->create(['is_active' => true, 'is_archived' => false, 'is_featured' => true, 'stock' => 5]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-labelledby="selection"', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-add-to-cart'));
    }

    public function test_the_commitments_are_only_facts_the_shop_keeps(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Commander sans surprise')
            ->assertSee('Sans compte obligatoire')
            ->assertSee('Annulation possible')
            ->assertDontSee('bio')->assertDontSee('cruauté');
    }

    public function test_at_most_three_testimonials_are_shown(): void
    {
        foreach (range(1, 5) as $i) {
            Testimonial::create([
                'customer_name' => "Cliente {$i}", 'comment' => "Avis numéro {$i}", 'rating' => 5,
                'show_on_home' => true, 'is_active' => true, 'order' => $i,
            ]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(3, preg_match_all('/Avis numéro \d/', $html));
    }

    public function test_the_page_ends_on_a_way_to_the_shop_when_there_is_no_bottom_banner(): void
    {
        $this->get('/')->assertOk()->assertSee('Voir la boutique');
    }

    public function test_the_new_sections_speak_english_in_english(): void
    {
        $this->rayon('Soins visage');

        $this->withHeader('Accept-Language', 'en')->get('/')->assertOk()
            ->assertSee('Order with no surprises')
            ->assertSee('Order without creating an account')
            ->assertDontSee('Commander sans surprise');
    }
}
