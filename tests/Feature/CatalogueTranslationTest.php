<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simonet85\LaravelDeepL\Facades\DeepL;
use Tests\TestCase;

class CatalogueTranslationTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        return Product::factory()->create([
            'name' => 'Huile nourrissante', 'short_description' => 'Pour tous les cheveux', 'stock' => 5,
            'full_description' => null, 'how_to_use' => null, 'ingredients' => null,
        ]);
    }

    private function translate(Product $product, string $field, string $value, ?string $hash): void
    {
        Translation::create([
            'translatable_type' => $product->getMorphClass(), 'translatable_id' => $product->id,
            'field' => $field, 'locale' => 'en', 'value' => $value, 'source_hash' => $hash,
        ]);
    }

    public function test_the_french_text_is_shown_in_french_and_the_translation_in_english(): void
    {
        $product = $this->product();
        $this->translate($product, 'name', 'Nourishing oil', md5('Huile nourrissante'));

        $this->get(route('shop.index'))->assertSee('Huile nourrissante')->assertDontSee('Nourishing oil');

        $this->withHeader('Accept-Language', 'en')->get(route('shop.index'))
            ->assertSee('Nourishing oil')->assertDontSee('Huile nourrissante');
    }

    public function test_a_field_without_translation_falls_back_to_french(): void
    {
        $product = $this->product();
        $this->translate($product, 'name', 'Nourishing oil', md5('Huile nourrissante'));

        $this->withHeader('Accept-Language', 'en')->get(route('products.show', $product->slug))
            ->assertSee('Nourishing oil')->assertSee('Pour tous les cheveux');
    }

    public function test_a_machine_translation_of_an_edited_text_is_ignored_until_renewed(): void
    {
        $product = $this->product();
        $this->translate($product, 'name', 'Nourishing oil', md5('Huile nourrissante'));
        $product->update(['name' => 'Huile réparatrice']);

        $this->withHeader('Accept-Language', 'en')->get(route('shop.index'))
            ->assertSee('Huile réparatrice')->assertDontSee('Nourishing oil');
    }

    public function test_a_hand_typed_translation_survives_an_edit_of_the_french(): void
    {
        $product = $this->product();
        $this->translate($product, 'name', 'Repairing oil', null);
        $product->update(['name' => 'Huile réparatrice']);

        $this->withHeader('Accept-Language', 'en')->get(route('shop.index'))->assertSee('Repairing oil');
    }

    public function test_the_sync_command_translates_what_is_missing_and_keeps_hand_typed_text(): void
    {
        $product = $this->product();
        $category = Category::factory()->create(['name' => 'Soins du corps', 'description' => null]);
        $this->translate($product, 'short_description', 'For every hair type', null);

        DeepL::shouldReceive('translate')->once()->withArgs(function ($texts, $locale, $source) {
            sort($texts);

            return $texts === ['Huile nourrissante', 'Soins du corps'] && $locale === 'en' && $source === 'fr';
        })->andReturnUsing(fn ($texts) => array_map(fn ($t) => $t === 'Soins du corps' ? 'Body care' : 'Nourishing oil', $texts));

        $this->artisan('translations:sync', ['locale' => 'en'])->assertSuccessful();

        $this->assertDatabaseHas('translations', ['translatable_id' => $category->id, 'field' => 'name', 'value' => 'Body care', 'source_hash' => md5('Soins du corps')]);
        $this->assertDatabaseHas('translations', ['translatable_id' => $product->id, 'field' => 'short_description', 'value' => 'For every hair type', 'source_hash' => null]);
    }

    public function test_the_sync_command_does_nothing_when_everything_is_up_to_date(): void
    {
        $product = $this->product();
        foreach (['name' => 'Nourishing oil', 'short_description' => 'For every hair'] as $field => $value) {
            $this->translate($product, $field, $value, md5($product->{$field}));
        }

        DeepL::shouldReceive('translate')->never();

        $this->artisan('translations:sync', ['locale' => 'en'])->expectsOutputToContain('0 text(s)')->assertSuccessful();
    }

    public function test_dry_run_sends_nothing(): void
    {
        $this->product();
        DeepL::shouldReceive('translate')->never();

        $this->artisan('translations:sync', ['locale' => 'en', '--dry-run' => true])->expectsOutputToContain('to translate')->assertSuccessful();
        $this->assertDatabaseCount('translations', 0);
    }

    public function test_the_default_language_cannot_be_a_target(): void
    {
        $this->artisan('translations:sync', ['locale' => 'fr'])->assertFailed();
    }
}
