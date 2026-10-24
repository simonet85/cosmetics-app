<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_hides_private_areas_and_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /checkout', false)
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_sitemap_lists_public_pages_and_only_sellable_products(): void
    {
        $live = Product::factory()->create(['is_active' => true, 'is_archived' => false]);
        $hidden = Product::factory()->create(['is_active' => false]);
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $response->assertSee(route('home'), false)
            ->assertSee(route('pages.faq'), false)
            ->assertSee(route('products.show', $live->slug), false)
            ->assertSee(route('shop.category', $category->slug), false)
            ->assertDontSee($hidden->slug, false)
            ->assertDontSee('/admin', false);
    }
}
