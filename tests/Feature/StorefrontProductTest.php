<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontProductTest extends TestCase
{
    use RefreshDatabase;

    private function review(Product $product, int $rating, bool $approved, array $extra = []): Review
    {
        return Review::create(array_merge([
            'product_id' => $product->id,
            'user_id' => User::factory()->create(['first_name' => 'Awa', 'last_name' => 'Koné'])->id,
            'rating' => $rating,
            'title' => 'Titre',
            'comment' => "Commentaire {$rating}",
            'is_approved' => $approved,
        ], $extra));
    }

    private function jsonLd(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);

        return json_decode($m[1] ?? 'null', true) ?? [];
    }

    public function test_both_product_urls_render_the_same_storefront_page(): void
    {
        $product = Product::factory()->create(['name' => 'Beurre de karité']);

        foreach ([route('products.show', $product->slug), route('shop.show', $product->slug)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, '<h1'));
            $this->assertStringContainsString('Beurre de karité', $html);
            $this->assertStringContainsString('data-add-to-cart', $html);
            $this->assertStringContainsString('id="content"', $html);
        }
    }

    public function test_only_approved_reviews_are_shown_and_counted(): void
    {
        $product = Product::factory()->create();
        $this->review($product, 5, true);
        $this->review($product, 3, true);
        $this->review($product, 1, false, ['comment' => 'Avis en attente de modération']);

        $html = $this->get(route('products.show', $product->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('Avis en attente de modération', $html);
        $this->assertStringContainsString('4,0 sur 5', $html);
        $this->assertStringContainsString('(2 avis)', $html);
        $this->assertStringContainsString('Awa K.', $html);
        $this->assertStringNotContainsString('Awa Koné', $html);
    }

    public function test_structured_data_describes_the_product_with_real_values(): void
    {
        $product = Product::factory()->discounted(8000)->create(['price' => 10000, 'sku' => 'SKU-TEST-1', 'stock' => 4]);
        $this->review($product, 4, true);

        $data = $this->jsonLd($this->get(route('products.show', $product->slug))->getContent());

        $this->assertSame('Product', $data['@type']);
        $this->assertSame('SKU-TEST-1', $data['sku']);
        $this->assertSame('XOF', $data['offers']['priceCurrency']);
        $this->assertSame('8000', $data['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $data['offers']['availability']);
        $this->assertSame(1, $data['aggregateRating']['reviewCount']);
    }

    public function test_no_rating_markup_without_reviews_and_out_of_stock_says_so(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $html = $this->get(route('products.show', $product->slug))->getContent();

        $this->assertArrayNotHasKey('aggregateRating', $this->jsonLd($html));
        $this->assertSame('https://schema.org/OutOfStock', $this->jsonLd($html)['offers']['availability']);
        $this->assertStringContainsString('Épuisé pour le moment', $html);
        $this->assertStringNotContainsString('data-label', $html);
    }

    public function test_variants_are_radios_with_the_sold_out_one_disabled(): void
    {
        $product = Product::factory()->create(['price' => 10000]);
        ProductVariant::factory()->for($product)->create(['size' => '50ml', 'stock' => 5, 'price_modifier' => 0]);
        ProductVariant::factory()->for($product)->create(['size' => '200ml', 'stock' => 0, 'price_modifier' => 3000]);

        $html = $this->get(route('products.show', $product->slug))->getContent();

        $this->assertSame(2, substr_count($html, 'name="variant_id"'));
        $this->assertMatchesRegularExpression('/name="variant_id"[^>]*checked/s', $html);
        $this->assertMatchesRegularExpression('/data-stock="0"[^>]*disabled/s', $html);
        $this->assertStringContainsString('(épuisé)', $html);
    }

    public function test_composition_and_usage_only_show_when_filled(): void
    {
        $empty = Product::factory()->create(['ingredients' => null, 'how_to_use' => null]);
        $full = Product::factory()->create(['ingredients' => "Eau, Aloe vera\nGlycérine", 'how_to_use' => 'Appliquer matin et soir.']);

        $this->get(route('products.show', $empty->slug))->assertDontSee('Composition')->assertDontSee("Conseils d'utilisation");
        $this->get(route('products.show', $full->slug))->assertSee('Aloe vera')->assertSee('Appliquer matin et soir.');
    }

    public function test_gallery_thumbnails_only_exist_with_several_photos(): void
    {
        $product = Product::factory()->create();
        ProductImage::create(['product_id' => $product->id, 'path' => 'images/products/product-01-330x440.jpg', 'is_primary' => true, 'order' => 1]);

        $this->get(route('products.show', $product->slug))->assertDontSee('data-gallery-thumb', false);

        ProductImage::create(['product_id' => $product->id, 'path' => 'images/products/product-02-330x440.jpg', 'is_primary' => false, 'order' => 2]);

        $this->get(route('products.show', $product->slug))->assertSee('data-gallery-thumb', false)->assertSee('Voir la photo 2 sur 2');
    }

    public function test_related_products_come_from_the_same_category_and_exclude_the_product(): void
    {
        $category = Category::factory()->create(['is_active' => true, 'parent_id' => null]);
        $product = Product::factory()->create(['name' => 'Produit principal']);
        $sibling = Product::factory()->create(['name' => 'Produit voisin']);
        $other = Product::factory()->create(['name' => 'Produit étranger']);
        $product->categories()->attach($category);
        $sibling->categories()->attach($category);

        $html = $this->get(route('products.show', $product->slug))->getContent();

        $this->assertStringContainsString('Produit voisin', $html);
        $this->assertStringNotContainsString('Produit étranger', $html);
        $this->assertSame(1, substr_count($html, 'Produit principal</h1>'));
    }

    public function test_wishlist_button_reflects_the_session(): void
    {
        $product = Product::factory()->create();

        $this->get(route('products.show', $product->slug))->assertSee('aria-pressed="false"', false);
        $this->withSession(['wishlist' => [$product->id]])
            ->get(route('products.show', $product->slug))->assertSee('aria-pressed="true"', false);
    }

    public function test_delivery_and_payment_lines_come_from_the_settings(): void
    {
        $product = Product::factory()->create();

        // No shipping cost: delivery is simply free, no threshold is invented
        $this->get(route('products.show', $product->slug))
            ->assertSee('Livraison gratuite')->assertDontSee('Livraison gratuite dès')
            ->assertSee('Mobile money');

        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'key' => 'standard_shipping_cost', 'value' => '2000', 'type' => 'text', 'group' => 'shipping',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('products.show', $product->slug))
            ->assertSee("Livraison gratuite dès 50\u{00A0}000\u{00A0}FCFA");
    }

    public function test_guests_are_sent_to_sign_in_and_customers_can_review_with_a_title(): void
    {
        $product = Product::factory()->create();
        $payload = ['rating' => 5, 'title' => 'Top', 'comment' => 'Très bon produit.'];

        $this->post(route('products.reviews.store', $product->slug), $payload)->assertRedirect(route('login'));
        $this->assertSame(0, Review::count());

        $this->actingAs(User::factory()->create())
            ->post(route('products.reviews.store', $product->slug), $payload)
            ->assertSessionHas('success');

        $review = Review::first();
        $this->assertFalse($review->is_approved);
        $this->assertSame('Top', $review->title);
    }

    public function test_the_review_form_asks_for_every_required_field(): void
    {
        $product = Product::factory()->create();
        $html = $this->actingAs(User::factory()->create())->get(route('products.show', $product->slug))->getContent();

        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('name="comment"', $html);
        $this->assertSame(5, substr_count($html, 'name="rating"'));

        $this->actingAs(User::factory()->create())
            ->post(route('products.reviews.store', $product->slug), ['rating' => 5, 'comment' => 'Sans titre'])
            ->assertSessionHasErrors('title');
    }

    public function test_admin_preview_of_an_unpublished_product_is_noindex(): void
    {
        $inactive = Product::factory()->create(['is_active' => false]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('shop.show', $inactive->slug))
            ->assertOk()->assertSee('noindex,nofollow', false);
    }
}
