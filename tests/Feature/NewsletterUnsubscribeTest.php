<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsletterUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private function subscriber(): NewsletterSubscriber
    {
        return NewsletterSubscriber::create([
            'email' => 'awa@example.com',
            'token' => Str::random(32),
            'subscribed_at' => now()->subDay(),
        ]);
    }

    public function test_the_public_link_unsubscribes_the_address(): void
    {
        $subscriber = $this->subscriber();

        $this->get(route('newsletter.unsubscribe', $subscriber->token))
            ->assertRedirect(route('home'))
            ->assertSessionHas('newsletter_success');

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_the_link_is_idempotent(): void
    {
        $subscriber = $this->subscriber();
        $subscriber->update(['unsubscribed_at' => now()->subHour()]);
        $original = $subscriber->fresh()->unsubscribed_at;

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertRedirect(route('home'));

        $this->assertEquals($original, $subscriber->fresh()->unsubscribed_at);
    }

    public function test_an_unknown_or_malformed_token_is_a_404(): void
    {
        $this->get(route('newsletter.unsubscribe', Str::random(32)))->assertNotFound();
        $this->get('/newsletter/unsubscribe/short')->assertNotFound();
    }

    public function test_an_admin_can_unsubscribe_and_reactivate_an_address(): void
    {
        $subscriber = $this->subscriber();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.newsletter.toggle', $subscriber))
            ->assertRedirect()
            ->assertSessionHas('success', 'Abonné désinscrit.');

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);

        $this->actingAs($admin)
            ->patch(route('admin.newsletter.toggle', $subscriber))
            ->assertSessionHas('success', 'Abonné réactivé.');

        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_managing_subscribers_is_for_admins_only(): void
    {
        $subscriber = $this->subscriber();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.newsletter.toggle', $subscriber))
            ->assertForbidden();
    }
}
