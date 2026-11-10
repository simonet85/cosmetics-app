<?php

namespace Tests\Feature;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCampaignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function campaign(array $attributes = []): NewsletterCampaign
    {
        return NewsletterCampaign::create(array_merge([
            'subject' => 'Nouveautés de la semaine',
            'body' => "Bonjour,\nDécouvrez nos nouveautés.",
            'status' => NewsletterCampaign::STATUS_DRAFT,
        ], $attributes));
    }

    private function subscriber(array $attributes = []): NewsletterSubscriber
    {
        return NewsletterSubscriber::create(array_merge([
            'email' => Str::random(8).'@example.com',
            'token' => Str::random(32),
            'subscribed_at' => now(),
        ], $attributes));
    }

    public function test_campaigns_are_admin_only(): void
    {
        $campaign = $this->campaign();
        $customer = User::factory()->create();

        $this->get(route('admin.campaigns.index'))->assertRedirect();
        $this->actingAs($customer)->get(route('admin.campaigns.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.campaigns.store'), [
            'subject' => 'Test',
            'body' => 'Contenu',
        ])->assertForbidden();
        $this->actingAs($customer)->post(route('admin.campaigns.send', $campaign))->assertForbidden();
    }

    public function test_a_campaign_is_created_as_a_draft(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.store'), [
                'subject' => 'Soldes de novembre',
                'body' => "Bonjour,\nProfitez de -20 %.",
            ])
            ->assertRedirect(route('admin.campaigns.index'))
            ->assertSessionHas('success');

        $campaign = NewsletterCampaign::sole();
        $this->assertSame(NewsletterCampaign::STATUS_DRAFT, $campaign->status);
        $this->assertSame('Soldes de novembre', $campaign->subject);
        $this->assertSame(0, $campaign->sent_count);
    }

    public function test_subject_and_body_are_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.store'), ['subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['subject', 'body']);

        $this->assertSame(0, NewsletterCampaign::count());
    }

    public function test_a_draft_can_be_updated_and_deleted(): void
    {
        $campaign = $this->campaign();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.campaigns.update', $campaign), [
                'subject' => 'Sujet corrigé',
                'body' => 'Contenu corrigé',
            ])
            ->assertRedirect(route('admin.campaigns.index'));

        $this->assertSame('Sujet corrigé', $campaign->fresh()->subject);

        $this->actingAs($admin)
            ->delete(route('admin.campaigns.destroy', $campaign))
            ->assertRedirect(route('admin.campaigns.index'));

        $this->assertSame(0, NewsletterCampaign::count());
    }

    public function test_a_started_campaign_can_no_longer_be_edited_or_deleted(): void
    {
        $campaign = $this->campaign(['status' => NewsletterCampaign::STATUS_SENDING]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.campaigns.edit', $campaign))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.campaigns.update', $campaign), [
            'subject' => 'Trop tard',
            'body' => 'Trop tard',
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.campaigns.destroy', $campaign))->assertForbidden();

        $this->assertSame(1, NewsletterCampaign::count());
    }

    public function test_the_preview_is_sandboxed_and_escapes_the_body(): void
    {
        $campaign = $this->campaign(['body' => '<script>alert(1)</script> Bonjour']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.campaigns.preview', $campaign))
            ->assertOk();

        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
        $html = $response->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_sending_switches_the_draft_to_sending(): void
    {
        $campaign = $this->campaign();
        $this->subscriber();

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.send', $campaign))
            ->assertRedirect(route('admin.campaigns.index'))
            ->assertSessionHas('success');

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_SENDING, $campaign->status);
        $this->assertNull($campaign->max_subscriber_id);
        $this->assertSame(0, $campaign->sent_count);
    }

    public function test_sending_is_refused_without_any_active_subscriber(): void
    {
        $campaign = $this->campaign();
        $this->subscriber(['unsubscribed_at' => now()]);

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.send', $campaign))
            ->assertSessionHas('error');

        $this->assertSame(NewsletterCampaign::STATUS_DRAFT, $campaign->fresh()->status);
    }

    public function test_a_sending_campaign_can_be_cancelled(): void
    {
        $campaign = $this->campaign([
            'status' => NewsletterCampaign::STATUS_SENDING,
            'recipients_count' => 10,
            'sent_count' => 3,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.campaigns.cancel', $campaign))
            ->assertRedirect(route('admin.campaigns.index'))
            ->assertSessionHas('success');

        $this->assertSame(NewsletterCampaign::STATUS_CANCELLED, $campaign->fresh()->status);
        $this->assertSame(3, $campaign->fresh()->sent_count);
    }

    public function test_the_list_shows_campaigns_and_the_send_action(): void
    {
        $campaign = $this->campaign();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.campaigns.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Nouveautés de la semaine', $html);
        $this->assertStringContainsString(route('admin.campaigns.send', $campaign), $html);
        $this->assertStringContainsString(route('admin.campaigns.preview', $campaign), $html);
    }
}
