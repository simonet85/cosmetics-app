<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsletterExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function subscriber(string $email, ?string $unsubscribedAt = null): NewsletterSubscriber
    {
        return NewsletterSubscriber::create([
            'email' => $email,
            'token' => Str::random(32),
            'subscribed_at' => now()->subDays(3),
            'unsubscribed_at' => $unsubscribedAt,
        ]);
    }

    public function test_the_export_is_for_admins_only(): void
    {
        $this->get(route('admin.newsletter.export'))->assertRedirect();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.newsletter.export'))
            ->assertForbidden();
    }

    public function test_the_filtered_subscribers_are_downloaded_as_a_semicolon_csv_with_a_bom(): void
    {
        $this->subscriber('awa@example.com');
        $this->subscriber('gone@example.com', now()->subDay()->toDateTimeString());

        $response = $this->actingAs($this->admin())
            ->get(route('admin.newsletter.export', ['status' => 'subscribed']))
            ->assertOk();

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename=newsletter-', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString("Date d'inscription", $content);
        $this->assertStringContainsString('Statut', $content);
        $this->assertStringContainsString('awa@example.com', $content);
        $this->assertStringContainsString(';Abonné', $content);
        $this->assertStringNotContainsString('gone@example.com', $content);
    }

    public function test_the_unsubscribed_filter_and_the_search_combine(): void
    {
        $this->subscriber('awa@example.com');
        $this->subscriber('koffi@example.com', now()->toDateTimeString());

        $content = $this->actingAs($this->admin())
            ->get(route('admin.newsletter.export', ['status' => 'unsubscribed', 'search' => 'koffi']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('koffi@example.com', $content);
        $this->assertStringContainsString('Désabonné', $content);
        $this->assertStringNotContainsString('awa@example.com', $content);
    }

    public function test_an_email_cannot_run_as_a_spreadsheet_formula(): void
    {
        $this->subscriber('=HYPERLINK("http://evil.example")');

        $content = $this->actingAs($this->admin())
            ->get(route('admin.newsletter.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $content);
        $this->assertStringNotContainsString(';=HYPERLINK', $content);
    }
}
