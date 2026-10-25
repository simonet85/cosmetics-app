<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    /** @return list<array<string, mixed>> every JSON-LD block of the page, decoded */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $matches);

        return array_map(fn ($json) => json_decode($json, true), $matches[1]);
    }

    private function node(array $blocks, string $type): ?array
    {
        foreach ($blocks as $block) {
            foreach (isset($block['@graph']) ? $block['@graph'] : [$block] as $node) {
                if (($node['@type'] ?? null) === $type) {
                    return $node;
                }
            }
        }

        return null;
    }

    public function test_the_home_page_describes_the_shop_and_its_search(): void
    {
        $blocks = $this->jsonLd($this->get('/')->assertOk()->getContent());

        $organization = $this->node($blocks, 'Organization');
        $website = $this->node($blocks, 'WebSite');

        $this->assertSame(config('app.name'), $organization['name']);
        $this->assertStringContainsString('images/others/logo-black-02.png', $organization['logo']);
        $this->assertStringContainsString('/shop?search={search_term_string}', $website['potentialAction']['target']['urlTemplate']);
    }

    public function test_a_product_page_is_a_product_for_search_engines_and_for_sharing(): void
    {
        $category = Category::factory()->create(['name' => 'Sérums', 'is_active' => true]);
        $product = Product::factory()->create(['name' => 'Sérum test', 'price' => 1000, 'discount_price' => null, 'stock' => 4, 'is_active' => true, 'is_archived' => false, 'short_description' => null, 'full_description' => 'Un sérum léger qui hydrate la peau toute la journée.']);
        $product->categories()->attach($category);

        $html = $this->get(route('products.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:type" content="product">', $html);
        $this->assertStringContainsString('<meta property="product:price:amount" content="1000">', $html);
        $this->assertStringContainsString('<meta property="product:price:currency" content="XOF">', $html);
        $this->assertStringContainsString('<meta property="og:image:alt" content="Sérum test">', $html);
        // no short description: the page starts the description from the full one
        $this->assertStringContainsString('<meta name="description" content="Un sérum léger qui hydrate la peau toute la journée.">', $html);

        $trail = $this->node($this->jsonLd($html), 'BreadcrumbList');
        $this->assertSame(['Accueil', 'Boutique', 'Sérums', 'Sérum test'], array_column($trail['itemListElement'], 'name'));
        $this->assertSame([1, 2, 3, 4], array_column($trail['itemListElement'], 'position'));
        $this->assertSame(route('products.show', $product->slug), $trail['itemListElement'][3]['item']);
    }

    public function test_the_other_pages_stay_websites_for_sharing(): void
    {
        $this->get('/')->assertSee('<meta property="og:type" content="website">', false);
        $this->get(route('shop.index'))->assertSee('<meta property="og:type" content="website">', false);
    }

    public function test_the_shop_and_its_rayons_carry_a_breadcrumb(): void
    {
        $category = Category::factory()->create(['name' => 'Crèmes', 'is_active' => true]);

        $shop = $this->node($this->jsonLd($this->get(route('shop.index'))->assertOk()->getContent()), 'BreadcrumbList');
        $this->assertSame(['Accueil', 'Boutique'], array_column($shop['itemListElement'], 'name'));

        $rayon = $this->node($this->jsonLd($this->get(route('shop.category', $category->slug))->assertOk()->getContent()), 'BreadcrumbList');
        $this->assertSame(['Accueil', 'Boutique', 'Crèmes'], array_column($rayon['itemListElement'], 'name'));
    }

    public function test_both_templates_carry_the_same_structured_data(): void
    {
        DB::table('settings')->insert([
            ['key' => 'storefront_template_home', 'value' => 'beauty', 'type' => 'text', 'group' => 'appearance', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'storefront_template_shop', 'value' => 'beauty', 'type' => 'text', 'group' => 'appearance', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->assertNotNull($this->node($this->jsonLd($this->get('/')->getContent()), 'Organization'));
        $this->assertNotNull($this->node($this->jsonLd($this->get(route('shop.index'))->getContent()), 'BreadcrumbList'));
    }
}
