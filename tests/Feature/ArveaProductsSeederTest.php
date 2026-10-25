<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Database\Seeders\ArveaProductsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArveaProductsSeederTest extends TestCase
{
    use RefreshDatabase;

    private function rayons(): void
    {
        foreach (['skincare' => 'Soins du visage', 'hair-care' => 'Soins capillaires', 'body-care' => 'Soins du corps'] as $slug => $name) {
            Category::factory()->create(['name' => $name, 'slug' => $slug, 'is_active' => true, 'parent_id' => null]);
        }
    }

    public function test_the_prices_are_converted_from_dinars_at_the_rate(): void
    {
        $this->assertSame(4.5, ArveaProductsSeeder::RATE);
        $this->assertSame([5400.0, null], ArveaProductsSeeder::prices(1200, null));
        // a crossed-out old price is the list price, the shown price is the promotion
        $this->assertSame([6075.0, 5400.0], ArveaProductsSeeder::prices(1200, 1350));
        $this->assertSame([2520.0, null], ArveaProductsSeeder::prices(560, null));
    }

    public function test_it_creates_the_twenty_products_with_their_photos_texts_and_tag(): void
    {
        $this->rayons();

        $this->seed(ArveaProductsSeeder::class);

        $products = Product::where('slug', 'like', 'arvea-%')->get();
        $this->assertCount(20, $products);
        $this->assertSame(20, Tag::where('slug', 'arvea')->firstOrFail()->products()->count());

        foreach ($products as $product) {
            $this->assertTrue($product->is_active, $product->slug);
            $this->assertSame(20, $product->stock, $product->slug);
            $this->assertStringStartsWith('ARV-', $product->sku);
            $this->assertNotEmpty($product->short_description, $product->slug);
            $this->assertNotEmpty($product->how_to_use, $product->slug);
            $this->assertNotEmpty($product->ingredients, $product->slug);
            $this->assertNotEmpty($product->precautions, $product->slug);
            $this->assertNotEmpty($product->storage_conditions, $product->slug);
            $this->assertGreaterThan(0, $product->images()->count(), $product->slug);
            $this->assertSame(1, $product->images()->where('is_primary', true)->count(), $product->slug);
            $this->assertGreaterThan(0, $product->categories()->count(), $product->slug);
        }
    }

    public function test_every_photo_of_the_data_file_exists_in_the_project(): void
    {
        $data = json_decode(file_get_contents(database_path('seeders/data/arvea_products.json')), true);

        $this->assertCount(20, $data);
        foreach ($data as $product) {
            foreach ($product['images'] as $file) {
                $this->assertFileExists(public_path('images/products/arvea/'.$file), $product['slug']);
            }
        }
    }

    public function test_the_masque_has_the_converted_prices_and_the_key_ingredients_of_the_reference(): void
    {
        $this->rayons();
        $this->seed(ArveaProductsSeeder::class);

        $masque = Product::where('slug', 'arvea-masque-cheveux')->firstOrFail();

        $this->assertEquals(6075, $masque->price);
        $this->assertEquals(5400, $masque->discount_price);
        $this->assertSame(11, $masque->discount_percentage);
        $this->assertSame('hair-care', $masque->categories()->first()->slug);
        $this->assertStringContainsString('Aloe vera : ', $masque->key_ingredients);
        $this->assertCount(5, explode("\n", $masque->key_ingredients));
    }

    public function test_the_products_show_on_the_shop_with_their_information_tabs(): void
    {
        $this->rayons();
        $this->seed(ArveaProductsSeeder::class);

        $this->get(route('shop.index', ['per_page' => 24]))->assertOk()->assertSee('Masque Nourrissant Cheveux');

        $html = $this->get(route('products.show', 'arvea-masque-cheveux'))->assertOk()->getContent();
        $this->assertStringContainsString('data-tabs', $html);
        $this->assertSame(4, substr_count($html, 'role="tab"'));
        $this->assertStringContainsString('ARGANIA SPINOSA SEED OIL', $html);
        $this->assertStringContainsString('images/products/arvea/masque-cheveux-1.webp', $html);
    }

    public function test_running_it_again_creates_nothing_and_never_overwrites_an_edit(): void
    {
        $this->rayons();
        $this->seed(ArveaProductsSeeder::class);

        Product::where('slug', 'arvea-masque-cheveux')->update(['price' => 9999, 'stock' => 3]);
        $this->seed(ArveaProductsSeeder::class);

        $this->assertSame(20, Product::where('slug', 'like', 'arvea-%')->count());
        $masque = Product::where('slug', 'arvea-masque-cheveux')->firstOrFail();
        $this->assertEquals(9999, $masque->price);
        $this->assertSame(3, $masque->stock);
    }

    public function test_a_missing_rayon_does_not_stop_the_import(): void
    {
        $this->seed(ArveaProductsSeeder::class);   // no rayon created: products exist, without a rayon

        $this->assertSame(20, Product::where('slug', 'like', 'arvea-%')->count());
        $this->assertSame(0, Product::where('slug', 'arvea-masque-cheveux')->first()->categories()->count());
    }
}
