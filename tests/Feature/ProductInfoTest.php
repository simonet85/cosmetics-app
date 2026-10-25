<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Translation;
use App\Models\User;
use Database\Seeders\DemoProductDetailsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductInfoTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'is_active' => true, 'is_archived' => false, 'stock' => 5,
            'how_to_use' => null, 'ingredients' => null, 'key_ingredients' => null, 'precautions' => null, 'storage_conditions' => null,
        ], $attributes));
    }

    private function page(Product $product, array $headers = []): string
    {
        return $this->withHeaders($headers)->get(route('products.show', $product->slug))->assertOk()->getContent();
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_a_product_without_any_information_has_no_tabs(): void
    {
        $this->assertStringNotContainsString('data-tabs', $this->page($this->product()));
    }

    public function test_only_the_tabs_with_text_are_shown_in_the_order_of_the_reference(): void
    {
        $product = $this->product(['how_to_use' => 'Appliquer matin et soir.', 'storage_conditions' => 'Conserver au frais.']);

        $html = $this->page($product);

        $this->assertStringContainsString('id="info-tab-usage"', $html);
        $this->assertStringContainsString('id="info-tab-storage"', $html);
        $this->assertStringNotContainsString('id="info-tab-ingredients"', $html);
        $this->assertStringNotContainsString('id="info-tab-precautions"', $html);
        $this->assertLessThan(strpos($html, 'id="info-tab-storage"'), strpos($html, 'id="info-tab-usage"'));
    }

    public function test_all_four_sections_follow_the_reference_order(): void
    {
        $html = $this->page($this->product([
            'how_to_use' => 'U', 'ingredients' => 'AQUA', 'precautions' => 'P', 'storage_conditions' => 'C',
        ]));

        $order = array_map(fn ($key) => strpos($html, 'id="info-tab-'.$key.'"'), ['usage', 'ingredients', 'precautions', 'storage']);
        $sorted = $order;
        sort($sorted);

        $this->assertSame($sorted, $order);
        $this->assertStringContainsString('Utilisation', $html);
        $this->assertStringContainsString('Ingrédients', $html);
        $this->assertStringContainsString('Précautions d&#039;emploi', $html);
        $this->assertStringContainsString('Conditions de conservation', $html);
    }

    public function test_the_tabs_follow_the_aria_pattern_with_the_first_one_selected(): void
    {
        $html = $this->page($this->product(['how_to_use' => 'U', 'precautions' => 'P']));

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertSame(2, substr_count($html, 'role="tab"'));
        $this->assertSame(2, substr_count($html, 'role="tabpanel"'));
        $this->assertMatchesRegularExpression('/id="info-tab-usage"[^>]*aria-controls="info-panel-usage"[^>]*aria-selected="true"/s', $html);
        $this->assertMatchesRegularExpression('/id="info-tab-precautions"[^>]*aria-controls="info-panel-precautions"[^>]*aria-selected="false"[^>]*tabindex="-1"/s', $html);
        $this->assertMatchesRegularExpression('/id="info-panel-precautions"[^>]*aria-labelledby="info-tab-precautions"[^>]*data-inactive/s', $html);
        $this->assertDoesNotMatchRegularExpression('/id="info-panel-usage"[^>]*data-inactive/s', $html);
    }

    public function test_every_panel_has_its_own_heading_for_visitors_without_scripts(): void
    {
        $html = $this->page($this->product(['how_to_use' => 'U', 'precautions' => 'P', 'storage_conditions' => 'C']));

        $this->assertSame(3, substr_count($html, 'class="tab-heading'));

        $css = file_get_contents(resource_path('css/storefront.css'));
        $this->assertStringContainsString("html:not(.js) [data-tabs] [role='tablist'] { display: none; }", $css);
        $this->assertStringContainsString("html.js [data-tabs] [role='tabpanel'][data-inactive] { display: none; }", $css);
    }

    public function test_key_ingredients_are_expandable_with_their_benefits(): void
    {
        $html = $this->page($this->product([
            'ingredients' => 'AQUA (WATER), ALOE BARBADENSIS LEAF JUICE',
            'key_ingredients' => "Aloe vera : Hydratant / Nourrissant / Apaisant\nGlycérine : Hydratante\nKératine",
        ]));

        $this->assertStringContainsString('AQUA (WATER), ALOE BARBADENSIS LEAF JUICE', $html);
        $this->assertSame(3, substr_count($html, '<details class="group">'));
        $this->assertStringContainsString('Hydratant · Nourrissant · Apaisant', $html);
        $this->assertStringContainsString('Kératine', $html);    // a line without ":" is a name only
    }

    public function test_key_ingredients_alone_are_enough_for_the_ingredients_tab(): void
    {
        $html = $this->page($this->product(['key_ingredients' => 'Huile d\'argan : Nourrissante']));

        $this->assertStringContainsString('id="info-tab-ingredients"', $html);
        $this->assertStringContainsString('Huile d&#039;argan', $html);
    }

    public function test_the_text_of_a_section_is_escaped_and_keeps_its_line_breaks(): void
    {
        $html = $this->page($this->product(['how_to_use' => "<script>alert(1)</script>\nDeuxième ligne"]));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('whitespace-pre-line', $html);
    }

    public function test_the_sections_are_translated_when_a_translation_exists(): void
    {
        $product = $this->product(['how_to_use' => 'Appliquer matin et soir.', 'precautions' => 'Usage externe.']);
        Translation::create(['translatable_type' => $product->getMorphClass(), 'translatable_id' => $product->id, 'field' => 'how_to_use', 'locale' => 'en', 'value' => 'Apply morning and evening.', 'source_hash' => null]);

        $html = $this->page($product, ['Accept-Language' => 'en']);

        $this->assertStringContainsString('Apply morning and evening.', $html);
        $this->assertStringContainsString('How to use', $html);
        $this->assertStringContainsString('Precautions', $html);
        $this->assertStringContainsString('Usage externe.', $html);   // no translation yet: the French text shows
        $this->assertStringNotContainsString('Appliquer matin et soir.', $html);
    }

    public function test_the_demo_seeder_fills_empty_sections_and_never_overwrites_a_typed_text(): void
    {
        $typed = $this->product(['slug' => 'nourishing-hair-oil', 'how_to_use' => 'Texte saisi par la gérante.']);

        $this->seed(DemoProductDetailsSeeder::class);

        $typed->refresh();
        $this->assertSame('Texte saisi par la gérante.', $typed->how_to_use);
        $this->assertStringContainsString('ARGANIA', $typed->ingredients);
        $this->assertNotEmpty($typed->precautions);
        $this->assertNotEmpty($typed->storage_conditions);
    }

    public function test_the_admin_saves_the_information_sections_when_creating_a_product(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name' => 'Masque test', 'sku' => 'MASK-1', 'description' => 'Un masque.', 'price' => 1200, 'stock' => 5, 'status' => 'active',
            'how_to_use' => 'Appliquer sur cheveux humides.', 'ingredients' => 'AQUA, GLYCERIN',
            'key_ingredients' => 'Aloe vera : Hydratant', 'precautions' => 'Usage externe.', 'storage_conditions' => 'Au frais.',
        ])->assertSessionHasNoErrors();

        $product = Product::where('sku', 'MASK-1')->firstOrFail();
        $this->assertSame('Appliquer sur cheveux humides.', $product->how_to_use);
        $this->assertSame('Aloe vera : Hydratant', $product->key_ingredients);
        $this->assertSame('Usage externe.', $product->precautions);
        $this->assertSame('Au frais.', $product->storage_conditions);
    }

    public function test_the_admin_edits_and_clears_the_sections_and_the_form_shows_them(): void
    {
        $admin = $this->admin();
        $product = $this->product(['precautions' => 'Ancien texte.']);

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()
            ->assertSee('Informations produit')->assertSee('Ancien texte.')->assertSee('name="storage_conditions"', false);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'name' => $product->name, 'sku' => $product->sku, 'description' => 'Description.', 'price' => 1000, 'stock' => 3, 'status' => 'active',
            'precautions' => '', 'storage_conditions' => 'Hors de portée des enfants.',
        ])->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertEmpty($product->precautions);
        $this->assertSame('Hors de portée des enfants.', $product->storage_conditions);
    }

    public function test_the_admin_refuses_a_section_that_is_far_too_long(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), [
            'name' => $product->name, 'sku' => $product->sku, 'description' => 'D', 'price' => 1000, 'stock' => 3, 'status' => 'active',
            'precautions' => str_repeat('a', 1600),
        ])->assertSessionHasErrors('precautions');
    }
}
