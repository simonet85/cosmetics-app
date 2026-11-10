<?php

namespace Tests\Feature;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class NewsletterCampaignSendTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $attributes = []): NewsletterCampaign
    {
        return NewsletterCampaign::create(array_merge([
            'subject' => 'Nouveautés',
            'body' => "Bonjour,\nVoici les nouveautés.",
            'status' => NewsletterCampaign::STATUS_SENDING,
        ], $attributes));
    }

    private function subscriber(string $email, array $attributes = []): NewsletterSubscriber
    {
        return NewsletterSubscriber::create(array_merge([
            'email' => $email,
            'token' => Str::random(32),
            'subscribed_at' => now(),
        ], $attributes));
    }

    public function test_only_active_subscribers_are_served_and_the_campaign_completes(): void
    {
        $active = $this->subscriber('active@example.com');
        $this->subscriber('gone@example.com', ['unsubscribed_at' => now()]);
        $campaign = $this->campaign();

        Mail::fake();

        $this->artisan('newsletter:send', ['--pause' => 0])->assertExitCode(0);

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo('active@example.com'));
        Mail::assertNotSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo('gone@example.com'));

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(1, $campaign->recipients_count);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame($active->id, $campaign->max_subscriber_id);
        $this->assertSame($active->id, $campaign->last_subscriber_id);
        $this->assertNotNull($campaign->sent_at);
    }

    public function test_subscribers_who_arrive_after_the_start_are_excluded(): void
    {
        $this->subscriber('first@example.com');
        $this->subscriber('second@example.com');
        $campaign = $this->campaign();

        Mail::fake();

        $this->artisan('newsletter:send', ['--limit' => 1, '--pause' => 0])->assertExitCode(0);
        $this->assertSame(NewsletterCampaign::STATUS_SENDING, $campaign->fresh()->status);

        $this->subscriber('late@example.com');
        $this->artisan('newsletter:send', ['--limit' => 10, '--pause' => 0])->assertExitCode(0);

        Mail::assertSent(NewsletterCampaignMail::class, 2);
        Mail::assertNotSent(NewsletterCampaignMail::class, fn ($mail) => $mail->hasTo('late@example.com'));

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(2, $campaign->recipients_count);
        $this->assertSame(2, $campaign->sent_count);
    }

    public function test_a_failed_recipient_is_counted_and_the_next_run_resumes(): void
    {
        $this->subscriber('a@example.com');
        $this->subscriber('boom@example.com');
        $this->subscriber('c@example.com');
        $campaign = $this->campaign();

        $fake = new class extends NewsletterMailer
        {
            /** @var list<string> */
            public array $delivered = [];

            public function send(NewsletterSubscriber $subscriber, NewsletterCampaign $campaign): void
            {
                if ($subscriber->email === 'boom@example.com') {
                    throw new RuntimeException('SMTP down');
                }

                $this->delivered[] = $subscriber->email;
            }
        };

        $this->app->instance(NewsletterMailer::class, $fake);

        $this->artisan('newsletter:send', ['--pause' => 0])->assertExitCode(0);

        $campaign->refresh();
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(1, $campaign->failed_count);
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(['a@example.com', 'c@example.com'], $fake->delivered);

        // A finished campaign is never replayed, so the failed address is not retried by accident
        $this->artisan('newsletter:send', ['--pause' => 0])->assertExitCode(0);
        $this->assertSame(['a@example.com', 'c@example.com'], $fake->delivered);
    }

    public function test_batches_resume_from_the_saved_cursor(): void
    {
        foreach (range(1, 5) as $i) {
            $this->subscriber("s{$i}@example.com");
        }
        $campaign = $this->campaign();

        Mail::fake();

        $this->artisan('newsletter:send', ['--limit' => 2, '--pause' => 0])->assertExitCode(0);
        $this->assertSame(2, $campaign->fresh()->sent_count);
        $this->assertSame(NewsletterCampaign::STATUS_SENDING, $campaign->fresh()->status);

        $this->artisan('newsletter:send', ['--limit' => 2, '--pause' => 0])->assertExitCode(0);
        $this->assertSame(4, $campaign->fresh()->sent_count);

        $this->artisan('newsletter:send', ['--limit' => 2, '--pause' => 0])->assertExitCode(0);

        $campaign->refresh();
        $this->assertSame(5, $campaign->sent_count);
        $this->assertSame(NewsletterCampaign::STATUS_SENT, $campaign->status);
        Mail::assertSent(NewsletterCampaignMail::class, 5);
    }

    public function test_the_message_carries_an_unsubscribe_header_and_link(): void
    {
        $subscriber = $this->subscriber('awa@example.com');
        $this->campaign();

        Mail::fake();
        $this->artisan('newsletter:send', ['--pause' => 0])->assertExitCode(0);

        $url = route('newsletter.unsubscribe', $subscriber->token);

        Mail::assertSent(NewsletterCampaignMail::class, function (NewsletterCampaignMail $mail) use ($url) {
            return $mail->headers()->text['List-Unsubscribe'] === '<'.$url.'>'
                && str_contains($mail->render(), $url);
        });
    }

    public function test_a_campaign_can_be_targeted_by_id(): void
    {
        $this->subscriber('awa@example.com');
        $target = $this->campaign(['subject' => 'Ciblée']);
        $other = $this->campaign(['subject' => 'Autre']);

        Mail::fake();

        $this->artisan('newsletter:send', ['campaign' => $target->id, '--pause' => 0])->assertExitCode(0);

        Mail::assertSent(NewsletterCampaignMail::class, 1);
        Mail::assertSent(NewsletterCampaignMail::class, fn ($mail) => $mail->campaign->id === $target->id);
        $this->assertSame(NewsletterCampaign::STATUS_SENDING, $other->fresh()->status);
    }

    public function test_cancelled_campaigns_are_ignored(): void
    {
        $this->subscriber('awa@example.com');
        $campaign = $this->campaign(['status' => NewsletterCampaign::STATUS_CANCELLED]);

        Mail::fake();
        $this->artisan('newsletter:send', ['--pause' => 0])->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertSame(NewsletterCampaign::STATUS_CANCELLED, $campaign->fresh()->status);
    }

    public function test_an_invalid_batch_size_falls_back_to_one(): void
    {
        foreach (range(1, 3) as $i) {
            $this->subscriber("s{$i}@example.com");
        }
        $campaign = $this->campaign();

        Mail::fake();
        $this->artisan('newsletter:send', ['--limit' => 0, '--pause' => 0])->assertExitCode(0);

        $campaign->refresh();
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(NewsletterCampaign::STATUS_SENDING, $campaign->status);
    }

    public function test_the_command_is_quiet_when_nothing_is_scheduled(): void
    {
        Mail::fake();

        $this->artisan('newsletter:send')
            ->expectsOutput('No newsletter campaign is ready to send.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }
}
