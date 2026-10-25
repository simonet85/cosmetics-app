<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use App\Support\StorefrontTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'appearance', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function beauty(): void
    {
        $this->setting('storefront_template_shop', 'beauty');
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge(['is_active' => true, 'is_archived' => false, 'stock' => 5], $attributes));
    }

    private function review(Product $product, bool $approved): Review
    {
        return Review::create([
            'product_id' => $product->id, 'user_id' => User::factory()->create()->id,
            'rating' => 5, 'title' => 'Titre', 'comment' => 'Commentaire', 'is_approved' => $approved,
        ]);
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_the_classic_templates_are_the_default(): void
    {
        $this->assertSame('classic', StorefrontTemplates::for('shop'));
        $this->assertSame('classic', StorefrontTemplates::for('home'));

        $this->product();
        $this->get(route('shop.index'))->assertOk()->assertDontSee('data-template="beauty"', false);
    }

    public function test_an_unknown_saved_template_falls_back_to_the_classic_one(): void
    {
        $this->setting('storefront_template_shop', 'nonexistent');
        $this->setting('storefront_template_home', 'invented');

        $this->assertSame('classic', StorefrontTemplates::for('shop'));
        $this->assertSame('classic', StorefrontTemplates::for('home'));
    }

    public function test_the_shop_uses_the_beauty_template_when_it_is_chosen(): void
    {
        $this->beauty();
        $this->product(['name' => 'Sérum test']);

        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-template="beauty"', $html);
        $this->assertStringContainsString('tpl-beauty', $html);
        $this->assertStringContainsString('Sérum test', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_the_category_page_follows_the_template_too(): void
    {
        $this->beauty();
        $category = Category::factory()->create(['name' => 'Sérums', 'is_active' => true, 'parent_id' => null]);
        $this->product()->categories()->attach($category);

        $this->get(route('shop.category', $category->slug))->assertOk()->assertSee('data-template="beauty"', false)->assertSee('Sérums');
    }

    public function test_an_admin_can_preview_a_template_but_a_visitor_cannot(): void
    {
        $this->product();

        $this->get(route('shop.index', ['template' => 'beauty']))->assertOk()->assertDontSee('data-template="beauty"', false);
        $this->actingAs($this->admin())->get(route('shop.index', ['template' => 'beauty']))->assertOk()->assertSee('data-template="beauty"', false);
    }

    public function test_the_rayon_strip_counts_the_products_on_sale(): void
    {
        $this->beauty();
        $category = Category::factory()->create(['name' => 'Crèmes', 'is_active' => true, 'parent_id' => null]);
        $this->product()->categories()->attach($category);
        $this->product()->categories()->attach($category);
        $this->product(['stock' => 0])->categories()->attach($category);

        $this->get(route('shop.index'))->assertOk()->assertSee('2 produits');
    }

    public function test_each_tile_shows_badges_computed_from_real_data(): void
    {
        $this->beauty();
        $this->product(['name' => 'Produit chaud', 'is_hot' => true]);
        $this->product(['name' => 'Produit remisé', 'price' => 1000, 'discount_price' => 800]);

        $html = $this->get(route('shop.index'))->assertOk()->getContent();

        $this->assertStringContainsString('>Hot<', $html);
        $this->assertStringContainsString('>Nouveau<', $html);
        $this->assertStringContainsString('-20 %', $html);
    }

    public function test_a_sold_out_product_is_labelled_when_the_list_includes_them(): void
    {
        $this->beauty();
        $this->product(['name' => 'Disparu', 'stock' => 0]);

        $this->get(route('shop.index'))->assertOk()->assertDontSee('Disparu');
        $this->get(route('shop.index', ['sold_out' => 1]))->assertOk()->assertSee('Disparu')->assertSee('Épuisé');
    }

    public function test_the_page_size_and_columns_follow_the_query_and_ignore_unknown_values(): void
    {
        $this->beauty();
        foreach (range(1, 11) as $i) {
            $this->product(['name' => "Produit {$i}"]);
        }

        $nine = $this->get(route('shop.index', ['per_page' => 9]))->assertOk()->getContent();
        $this->assertSame(9, preg_match_all('/<article/', $nine));

        $default = $this->get(route('shop.index', ['per_page' => 7]))->assertOk()->getContent();
        $this->assertSame(11, preg_match_all('/<article/', $default));   // 12 per page: all 11 fit

        $this->get(route('shop.index', ['cols' => 4]))->assertOk()->assertSee('lg:grid-cols-4', false);
        $this->get(route('shop.index', ['cols' => 9]))->assertOk()->assertSee('lg:grid-cols-3', false);
    }

    public function test_the_on_sale_filter_keeps_only_discounted_products(): void
    {
        $this->beauty();
        $this->product(['name' => 'Plein tarif', 'discount_price' => null]);
        $this->product(['name' => 'En promo', 'price' => 1000, 'discount_price' => 700]);

        $this->get(route('shop.index', ['on_sale' => 1]))->assertOk()->assertSee('En promo')->assertDontSee('Plein tarif');
    }

    public function test_the_capacity_filter_lists_real_sizes_and_filters_by_them(): void
    {
        $this->beauty();
        $small = $this->product(['name' => 'Petit flacon']);
        $big = $this->product(['name' => 'Grand flacon']);
        ProductVariant::factory()->create(['product_id' => $small->id, 'size' => '50ml', 'stock' => 3, 'is_active' => true]);
        ProductVariant::factory()->create(['product_id' => $big->id, 'size' => '200ml', 'stock' => 3, 'is_active' => true]);

        $this->get(route('shop.index'))->assertOk()->assertSee('name="capacity[]"', false)->assertSee('50ml')->assertSee('200ml');
        $this->get(route('shop.index', ['capacity' => ['50ml']]))->assertOk()->assertSee('Petit flacon')->assertDontSee('Grand flacon');
    }

    public function test_the_best_rated_products_come_from_approved_reviews_only(): void
    {
        $this->beauty();
        $good = $this->product(['name' => 'Très apprécié']);
        $unmoderated = $this->product(['name' => 'Non modéré']);
        $this->review($good, true);
        $this->review($unmoderated, false);

        $html = $this->get(route('shop.index'))->assertOk()->getContent();
        $top = substr($html, strpos($html, 'id="top-rated"'));
        $top = substr($top, 0, strpos($top, '</section>'));

        $this->assertStringContainsString('Très apprécié', $top);
        $this->assertStringNotContainsString('Non modéré', $top);
    }

    public function test_the_sort_and_filters_forms_keep_the_other_choices(): void
    {
        $this->beauty();
        $this->product();

        $html = $this->get(route('shop.index', ['per_page' => 18, 'cols' => 4, 'sort' => 'price_asc']))->assertOk()->getContent();

        $this->assertStringContainsString('name="per_page" value="18"', $html);
        $this->assertStringContainsString('name="cols" value="4"', $html);
        $this->assertStringContainsString('data-autosubmit', $html);
    }

    public function test_the_beauty_shop_speaks_english_in_english(): void
    {
        $this->beauty();
        $this->review($this->product(['is_hot' => true]), true);

        $this->withHeader('Accept-Language', 'en')->get(route('shop.index'))->assertOk()
            ->assertSee('Filter by price')->assertSee('Top rated products')->assertSee('Availability')->assertSee('Show')
            ->assertDontSee('Filtrer par prix');
    }

    public function test_the_admin_settings_page_offers_the_templates_and_saves_the_choice(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk()
            ->assertSee('Apparence de la boutique')->assertSee('Glowing Beauty')->assertSee('storefront_template_shop', false);

        $this->actingAs($admin)->put(route('admin.settings.update'), ['storefront_template_shop' => 'beauty'])->assertSessionHasNoErrors();
        $this->assertSame('beauty', StorefrontTemplates::for('shop'));

        $this->actingAs($admin)->put(route('admin.settings.update'), ['storefront_template_shop' => 'classic'])->assertSessionHasNoErrors();
        $this->assertSame('classic', StorefrontTemplates::for('shop'));
    }

    public function test_the_admin_cannot_save_a_template_that_does_not_exist_for_a_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update'), ['storefront_template_shop' => 'invented'])->assertSessionHasErrors('storefront_template_shop');
        $this->actingAs($admin)->put(route('admin.settings.update'), ['storefront_template_home' => 'invented'])->assertSessionHasErrors('storefront_template_home');
    }
}
