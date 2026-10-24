<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminBannersTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> files written under public/ by a test, removed afterwards */
    private array $written = [];

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            @unlink(public_path($path));
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'promotional', 'position' => 'home_top', 'title' => 'Soldes de saison', 'subtitle' => 'Jusqu\'à dimanche',
            'order' => 1, 'is_active' => '1', 'link_url' => 'https://example.com/promo', 'button_text' => 'Voir',
            'image' => UploadedFile::fake()->image('promo.jpg', 800, 400),
        ], $overrides);
    }

    private function banner(array $attributes = []): Banner
    {
        return Banner::create(array_merge([
            'type' => 'promotional', 'position' => 'home_top', 'title' => 'Bannière', 'image_path' => 'images/hero-slider/hero-slider-15.jpg',
            'order' => 1, 'is_active' => true,
        ], $attributes));
    }

    public function test_banners_are_for_admins_only(): void
    {
        $this->get(route('admin.banners.index'))->assertRedirect();
        $this->actingAs(User::factory()->create())->get(route('admin.banners.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.banners.index'))->assertOk();
    }

    public function test_the_list_shows_banners_and_filters_by_type_position_and_status(): void
    {
        $this->banner(['title' => 'Haut actif', 'position' => 'home_top']);
        $this->banner(['title' => 'Bas inactif', 'position' => 'home_bottom', 'is_active' => false]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.banners.index'))->assertOk()->assertSee('Haut actif')->assertSee('Bas inactif');
        $this->actingAs($admin)->get(route('admin.banners.index', ['position' => 'home_top']))->assertSee('Haut actif')->assertDontSee('Bas inactif');
        $this->actingAs($admin)->get(route('admin.banners.index', ['status' => 'inactive']))->assertSee('Bas inactif')->assertDontSee('Haut actif');
    }

    public function test_the_list_refuses_an_unknown_sort_column_instead_of_failing(): void
    {
        $this->banner();

        $this->actingAs($this->admin())->get(route('admin.banners.index', ['sort_by' => 'no_such_column; drop table banners', 'sort_order' => 'sideways']))
            ->assertOk();
    }

    public function test_a_banner_can_be_created_with_an_image(): void
    {
        $this->actingAs($this->admin())->post(route('admin.banners.store'), $this->payload())
            ->assertRedirect(route('admin.banners.index'))->assertSessionHas('success');

        $banner = Banner::firstOrFail();
        $this->written[] = $banner->image_path;

        $this->assertSame('Soldes de saison', $banner->title);
        $this->assertTrue($banner->is_active);
        $this->assertStringStartsWith('storage/images/banners/', $banner->image_path);
        $this->assertFileExists(public_path($banner->image_path));
    }

    public function test_creation_validates_the_required_fields_and_the_image(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.banners.store'), ['order' => 1])
            ->assertSessionHasErrors(['type', 'position', 'title', 'image']);

        $this->actingAs($admin)->post(route('admin.banners.store'), $this->payload(['image' => UploadedFile::fake()->create('virus.exe', 10)]))
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)->post(route('admin.banners.store'), $this->payload(['start_date' => '2026-12-31', 'end_date' => '2026-01-01']))
            ->assertSessionHasErrors('end_date');

        $this->assertSame(0, Banner::count());
    }

    public function test_a_banner_can_be_edited_and_switched_off(): void
    {
        $banner = $this->banner(['title' => 'Avant']);

        $this->actingAs($this->admin())->put(route('admin.banners.update', $banner), [
            'type' => 'promotional', 'position' => 'home_middle', 'title' => 'Après', 'order' => 3,
        ])->assertRedirect(route('admin.banners.index'));

        $fresh = $banner->fresh();
        $this->assertSame('Après', $fresh->title);
        $this->assertSame('home_middle', $fresh->position);
        $this->assertFalse($fresh->is_active);   // the checkbox was not sent
        $this->assertSame('images/hero-slider/hero-slider-15.jpg', $fresh->image_path);   // no new image: the old one stays
    }

    public function test_replacing_the_image_removes_the_uploaded_one_but_not_shared_site_images(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.banners.store'), $this->payload());
        $banner = Banner::firstOrFail();
        $first = $banner->image_path;
        $this->written[] = $first;

        $this->actingAs($admin)->put(route('admin.banners.update', $banner), [
            'type' => 'promotional', 'position' => 'home_top', 'title' => 'Nouvelle image', 'order' => 1, 'is_active' => '1',
            'image' => UploadedFile::fake()->image('new.png', 800, 400),
        ])->assertSessionHas('success');
        $second = $banner->fresh()->image_path;
        $this->written[] = $second;

        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist(public_path($first));
        $this->assertFileExists(public_path($second));
    }

    public function test_deleting_a_banner_removes_it_and_its_uploaded_image(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.banners.store'), $this->payload());
        $banner = Banner::firstOrFail();
        $path = $banner->image_path;
        $this->written[] = $path;

        $this->actingAs($admin)->delete(route('admin.banners.destroy', $banner))->assertSessionHas('success');

        $this->assertSame(0, Banner::count());
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_banners_seeded_with_the_old_type_and_position_values_can_still_be_edited(): void
    {
        $legacy = $this->banner(['type' => 'promo', 'position' => 'promo', 'title' => 'Ancienne bannière']);

        $html = $this->actingAs($this->admin())->get(route('admin.banners.edit', $legacy))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="promo"[^>]*selected/', $html);

        $this->actingAs($this->admin())->put(route('admin.banners.update', $legacy), [
            'type' => 'promo', 'position' => 'promo', 'title' => 'Ancienne bannière modifiée', 'order' => 1, 'is_active' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame('promo', $legacy->fresh()->position);
    }

    public function test_the_live_scope_respects_the_schedule(): void
    {
        $this->banner(['title' => 'Toujours']);
        $this->banner(['title' => 'Passée', 'start_date' => now()->subDays(10), 'end_date' => now()->subDay()]);
        $this->banner(['title' => 'À venir', 'start_date' => now()->addDays(3)]);
        $this->banner(['title' => 'En cours', 'start_date' => now()->subDay(), 'end_date' => now()->addDay()]);
        $this->banner(['title' => 'Éteinte', 'is_active' => false]);

        $this->assertEqualsCanonicalizing(['Toujours', 'En cours'], Banner::live()->pluck('title')->all());
    }
}
