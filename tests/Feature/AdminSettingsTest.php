<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_invalid_settings_are_rejected_and_not_saved(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.settings.index'))->put(route('admin.settings.update'), [
            'site_name' => 'Ne doit pas être enregistré',
            'contact_email' => 'not-an-email',
            'from_email' => 'also-bad',
            'tax_rate' => 'abc',
            'free_shipping_threshold' => '-5',
            'site_logo' => UploadedFile::fake()->create('facture.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors(['site_logo', 'contact_email', 'from_email', 'tax_rate', 'free_shipping_threshold']);

        $this->assertNull(DB::table('settings')->where('key', 'site_name')->value('value'));
        $this->assertNull(DB::table('settings')->where('key', 'contact_email')->value('value'));
    }

    public function test_validation_errors_are_shown_on_the_settings_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.settings.index'))->followingRedirects()
            ->put(route('admin.settings.update'), ['contact_email' => 'not-an-email'])
            ->assertOk()
            ->assertSee('Certaines informations sont invalides.')
            ->assertSee('L\'adresse e-mail de contact doit être valide.');
    }

    public function test_settings_are_cached_and_the_cache_is_cleared_on_update(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_name' => 'Alpha'])->assertSessionHas('success');
        $this->assertSame('Alpha', getSetting('site_name'));

        // A write that bypasses the application must not refresh the cache
        DB::table('settings')->where('key', 'site_name')->update(['value' => 'Changed behind the cache']);
        $this->assertSame('Alpha', getSetting('site_name'));

        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_name' => 'Beta'])->assertSessionHas('success');
        $this->assertSame('Beta', getSetting('site_name'));
    }

    public function test_a_new_logo_replaces_the_previous_file_safely(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_logo' => UploadedFile::fake()->image('logo.png', 300, 100),
        ])->assertSessionHas('success');

        $first = DB::table('settings')->where('key', 'site_logo')->value('value');
        $this->assertNotNull($first);
        $this->assertFileExists(public_path($first));

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_logo' => UploadedFile::fake()->image('logo-2.png', 300, 100),
        ])->assertSessionHas('success');

        $second = DB::table('settings')->where('key', 'site_logo')->value('value');
        $this->assertNotSame($first, $second);
        $this->assertFileExists(public_path($second));
        $this->assertFileDoesNotExist(public_path($first));

        @unlink(public_path($second));
    }

    public function test_settings_can_only_be_saved_by_admins(): void
    {
        $this->put(route('admin.settings.update'), ['site_name' => 'Nope'])->assertRedirect();
        $this->actingAs(User::factory()->create())->put(route('admin.settings.update'), ['site_name' => 'Nope'])->assertForbidden();

        $this->assertNull(DB::table('settings')->where('key', 'site_name')->value('value'));
    }
}
