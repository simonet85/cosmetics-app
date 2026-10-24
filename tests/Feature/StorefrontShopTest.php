<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontShopTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, float $price, array $extra = []): Product
    {
        return Product::factory()->create(array_merge(['name' => $name, 'price' => $price], $extra));
    }

    public function test_the_shop_lists_real_products_with_net_prices_and_cart_buttons(): void
    {
        $this->product('Crème Karité', 8500);

        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('Crème Karité', $html);
        $this->assertStringContainsString("8\u{00A0}500", $html);
        $this->assertStringContainsString('data-add-to-cart', $html);
        $this->assertStringContainsString('1 produit', $html);
    }

    public function test_inactive_and_sold_out_products_are_hidden_unless_asked(): void
    {
        $this->product('Visible', 1000);
        $this->product('Désactivé', 1000, ['is_active' => false]);
        $this->product('Épuisé', 1000, ['stock' => 0]);

        $this->get(route('shop.index'))->assertSee('Visible')->assertDontSee('Désactivé')->assertDontSee('Épuisé');
        $this->get(route('shop.index', ['sold_out' => 1]))->assertSee('Épuisé')->assertDontSee('Désactivé');
    }

    public function test_price_filter_uses_the_price_actually_paid(): void
    {
        $this->product('Promo', 20000, ['discount_price' => 8000]);
        $this->product('Cher', 15000);
        $this->product('Moyen', 9000);

        $this->get(route('shop.index', ['max_price' => 9000]))
            ->assertSee('Promo')->assertSee('Moyen')->assertDontSee('Cher');

        $this->get(route('shop.index', ['min_price' => 10000]))
            ->assertSee('Cher')->assertDontSee('Promo')->assertDontSee('Moyen');
    }

    public function test_sorting_by_price_uses_the_discounted_price(): void
    {
        $this->product('Alpha', 20000, ['discount_price' => 3000]);
        $this->product('Bravo', 5000);
        $this->product('Charlie', 1000);

        $asc = $this->get(route('shop.index', ['sort' => 'price_asc']))->getContent();
        $this->assertLessThan(strpos($asc, 'Alpha'), strpos($asc, 'Charlie'));
        $this->assertLessThan(strpos($asc, 'Bravo'), strpos($asc, 'Alpha'));

        $desc = $this->get(route('shop.index', ['sort' => 'price_desc']))->getContent();
        $this->assertLessThan(strpos($desc, 'Alpha'), strpos($desc, 'Bravo'));
    }

    public function test_category_and_tag_filters_and_the_category_page(): void
    {
        $face = Category::factory()->create(['name' => 'Visage', 'slug' => 'visage', 'is_active' => true, 'parent_id' => null]);
        $body = Category::factory()->create(['name' => 'Corps', 'slug' => 'corps', 'is_active' => true, 'parent_id' => null]);
        $tag = Tag::create(['name' => 'Bio', 'slug' => 'bio']);

        $serum = $this->product('Sérum Visage', 12000);
        $serum->categories()->attach($face);
        $serum->tags()->attach($tag);
        $this->product('Lait Corps', 7000)->categories()->attach($body);

        $this->get(route('shop.index', ['category' => 'visage']))->assertSee('Sérum Visage')->assertDontSee('Lait Corps');
        $this->get(route('shop.category', 'corps'))->assertSee('Lait Corps')->assertDontSee('Sérum Visage');
        $this->get(route('shop.index', ['tag' => 'bio']))->assertSee('Sérum Visage')->assertDontSee('Lait Corps');
    }

    public function test_search_matches_names_and_treats_wildcards_literally(): void
    {
        $this->product('Huile de coco', 4000);
        $this->product('Gel douche', 3000);

        $this->get(route('shop.index', ['search' => 'coco']))->assertSee('Huile de coco')->assertDontSee('Gel douche');
        $this->get(route('shop.index', ['search' => '%']))->assertDontSee('Huile de coco')->assertDontSee('Gel douche');
    }

    public function test_invalid_filters_are_rejected_instead_of_trusted(): void
    {
        $this->get(route('shop.index', ['min_price' => 'abc']))->assertSessionHasErrors('min_price');
        $this->get(route('shop.index', ['sort' => 'drop table']))->assertSessionHasErrors('sort');
        $this->get(route('shop.index', ['max_price' => -5]))->assertSessionHasErrors('max_price');
    }

    public function test_pagination_keeps_the_filters_in_the_url(): void
    {
        foreach (range(1, 14) as $i) {
            $this->product("Produit {$i}", 1000 + $i);
        }

        $html = $this->get(route('shop.index', ['sort' => 'price_asc']))->assertOk()->getContent();

        $this->assertStringContainsString('Page 1 sur 2', $html);
        $this->assertStringContainsString('sort=price_asc', $html);
        $this->assertStringContainsString('page=2', $html);
        $this->get(route('shop.index', ['sort' => 'price_asc', 'page' => 2]))->assertSee('Page 2 sur 2');
    }

    public function test_active_filters_show_as_removable_chips_and_filtered_pages_are_noindex(): void
    {
        $this->product('Produit', 5000);

        $html = $this->get(route('shop.index', ['min_price' => 1000, 'sort' => 'price_asc']))->getContent();

        $this->assertStringContainsString('dès 1&nbsp;000&nbsp;FCFA', str_replace("\u{00A0}", '&nbsp;', $html));
        $this->assertStringContainsString('retirer ce filtre', $html);
        $this->assertStringContainsString('noindex,follow', $html);
        $this->assertStringNotContainsString('noindex', $this->get(route('shop.index'))->getContent());
    }

    public function test_no_result_page_offers_a_way_out(): void
    {
        $this->get(route('shop.index', ['search' => 'introuvable']))
            ->assertOk()->assertSee('Aucun produit ne correspond')->assertSee('Voir toute la boutique');
    }

    public function test_an_inactive_category_is_a_404(): void
    {
        $cat = Category::factory()->create(['slug' => 'cachee', 'is_active' => false, 'parent_id' => null]);

        $this->get(route('shop.category', $cat->slug))->assertNotFound();
    }
}
