<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\StorefrontTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomeBeautyTest extends TestCase
{
    use RefreshDatabase;

    private function setting(string $key, ?string $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], [
            'value' => $value, 'type' => 'text', 'group' => 'appearance', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function beauty(): void
    {
        $this->setting('storefront_template_home', 'beauty');
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge(['is_active' => true, 'is_archived' => false, 'stock' => 5], $attributes));
    }

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_the_classic_home_is_the_default_and_the_beauty_one_is_chosen_in_the_settings(): void
    {
        $this->product(['is_featured' => true]);

        $this->get('/')->assertOk()->assertDontSee('data-template="beauty"', false);

        $this->beauty();
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-template="beauty"', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_an_admin_can_preview_the_beauty_home_without_saving_it(): void
    {
        $this->get('/?template=beauty')->assertOk()->assertDontSee('data-template="beauty"', false);
        $this->actingAs($this->admin())->get('/?template=beauty')->assertOk()->assertSee('data-template="beauty"', false);
    }

    public function test_the_hero_is_a_slider_that_always_has_several_slides(): void
    {
        $this->beauty();
        foreach (range(1, 4) as $i) {
            $this->product(['is_featured' => true]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-roledescription="carousel"', $html);
        $this->assertSame(3, substr_count($html, 'aria-roledescription="slide"'));
        $this->assertStringContainsString('data-slider-prev', $html);
        $this->assertStringContainsString('data-slider-next', $html);
        // the brand slide comes first when there is no banner
        $this->assertStringContainsString('images/templates/beauty/hero.jpg', $html);
    }

    public function test_admin_banners_become_the_hero_slides(): void
    {
        $this->beauty();
        Banner::create(['type' => 'hero', 'position' => 'home_top', 'title' => 'Ma bannière', 'subtitle' => 'accent', 'image_path' => 'images/x.jpg', 'link_url' => '/shop', 'button_text' => 'Go', 'is_active' => true, 'order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Ma bannière', $html);
        $this->assertStringNotContainsString('images/templates/beauty/hero.jpg', $html);
    }

    public function test_three_rayons_with_a_product_photo_link_to_their_page(): void
    {
        $this->beauty();
        $slugs = [];
        foreach (['Visage', 'Corps', 'Cheveux', 'Parfums'] as $name) {
            $category = Category::factory()->create(['name' => $name, 'is_active' => true, 'parent_id' => null]);
            $this->product()->categories()->attach($category);
            $slugs[] = $category->slug;
        }

        $html = $this->get('/')->assertOk()->getContent();
        $block = substr($html, strpos($html, 'aria-labelledby="rayons"'));
        $block = substr($block, 0, strpos($block, '</section>'));

        $this->assertSame(3, substr_count($block, '<li class="relative text-center">'));
        $this->assertStringContainsString(route('shop.category', $slugs[0]), $block);
    }

    public function test_best_sellers_and_featured_products_are_two_shelves(): void
    {
        $this->beauty();
        foreach (range(1, 3) as $i) {
            $this->product(['name' => "Vendu {$i}", 'is_best_seller' => true]);
        }
        foreach (range(1, 3) as $i) {
            $this->product(['name' => "Vedette {$i}", 'is_featured' => true]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-labelledby="best-sellers"', $html);
        $this->assertStringContainsString('aria-labelledby="featured"', $html);
        foreach (range(1, 3) as $i) {
            $this->assertStringContainsString("Vendu {$i}", $html);
        }
    }

    public function test_the_before_after_comparison_is_a_labelled_range_control(): void
    {
        $this->beauty();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-compare', $html);
        $this->assertStringContainsString('type="range"', $html);
        $this->assertStringContainsString('aria-label="Comparer avant et après"', $html);
        $this->assertStringContainsString('images/templates/beauty/before.jpg', $html);
        $this->assertStringContainsString('images/templates/beauty/after.jpg', $html);
    }

    public function test_the_offer_with_countdown_needs_a_future_end_date(): void
    {
        $this->beauty();

        $this->get('/')->assertOk()->assertDontSee('data-countdown', false);

        $this->setting('home_offer_ends_at', now()->subDay()->format('Y-m-d\TH:i'));
        $this->get('/')->assertOk()->assertDontSee('data-countdown', false);

        $this->setting('home_offer_ends_at', now()->addDays(3)->format('Y-m-d\TH:i'));
        $this->setting('home_offer_title', 'Grande offre');
        $this->setting('home_offer_text', 'Jusqu\'à dimanche.');
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-countdown', $html);
        $this->assertStringContainsString('Grande offre', $html);
        $this->assertStringContainsString('data-unit="days"', $html);
        $this->assertStringContainsString('data-unit="seconds"', $html);
        $this->assertMatchesRegularExpression('/data-unit="days">0[23]</', $html);
    }

    public function test_a_broken_end_date_hides_the_offer_instead_of_failing(): void
    {
        $this->beauty();
        $this->setting('home_offer_ends_at', 'pas une date');

        $this->get('/')->assertOk()->assertDontSee('data-countdown', false);
    }

    public function test_the_admin_saves_the_offer_and_an_empty_date_switches_it_off(): void
    {
        $admin = $this->admin();
        $end = now()->addWeek()->format('Y-m-d\TH:i');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'home_offer_ends_at' => $end, 'home_offer_title' => 'Offre', 'home_offer_text' => 'Texte',
        ])->assertSessionHasNoErrors();
        $this->assertSame($end, getSetting('home_offer_ends_at'));

        $this->actingAs($admin)->put(route('admin.settings.update'), ['home_offer_ends_at' => ''])->assertSessionHasNoErrors();
        $this->assertEmpty(getSetting('home_offer_ends_at'));

        $this->actingAs($admin)->put(route('admin.settings.update'), ['home_offer_ends_at' => 'nope'])->assertSessionHasErrors('home_offer_ends_at');
    }

    public function test_the_beauty_home_speaks_english_in_english(): void
    {
        $this->beauty();
        $this->product(['is_best_seller' => true, 'is_featured' => true]);
        $this->setting('home_offer_ends_at', now()->addDay()->format('Y-m-d\TH:i'));

        $this->withHeader('Accept-Language', 'en')->get('/')->assertOk()
            ->assertSee('Drag to compare')->assertSee('Limited-time offer')->assertSee('Reveal your skin')
            ->assertDontSee('Faites glisser pour comparer');
    }

    public function test_the_choice_is_saved_through_the_settings_page(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update'), ['storefront_template_home' => 'beauty'])->assertSessionHasNoErrors();

        $this->assertSame('beauty', StorefrontTemplates::for('home'));
    }
}
