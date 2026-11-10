<?php

namespace App\Console\Commands;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends the campaigns in "sending" state, a small batch per run.
 *
 * The production host has no queue worker and disables proc_open, so this runs synchronously inside the
 * scheduler tick and must finish well under a minute. Progress is saved per recipient: a crash or a
 * timeout never resends to someone already served, and the next tick resumes where this one stopped.
 */
class SendNewsletterCampaign extends Command
{
    protected $signature = 'newsletter:send
        {campaign? : Campaign id to send (all sending campaigns by default)}
        {--limit=20 : Maximum recipients per run}
        {--pause=500 : Milliseconds to wait between two messages}
        {--max-seconds=50 : Stop the run after this many seconds, leaving the rest for the next tick}';

    protected $description = 'Send newsletter campaigns to active subscribers, in resumable batches';

    private NewsletterMailer $mailer;

    private int $pause;

    private int $sent = 0;

    private int $failed = 0;

    public function handle(NewsletterMailer $mailer): int
    {
        $this->mailer = $mailer;
        $this->pause = max(0, (int) $this->option('pause'));
        $limit = max(1, (int) $this->option('limit'));
        $deadline = microtime(true) + max(1, (int) $this->option('max-seconds'));

        $campaigns = NewsletterCampaign::sending()
            ->when($this->argument('campaign'), fn ($query, $id) => $query->whereKey($id))
            ->orderBy('id')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No newsletter campaign is ready to send.');

            return self::SUCCESS;
        }

        $remaining = $limit;

        foreach ($campaigns as $campaign) {
            $remaining = $this->process($campaign, $remaining, $deadline);

            // 0: the run reached its recipient cap; -1: the time budget is exhausted
            if ($remaining <= 0) {
                $this->info('Batch finished, the next tick resumes the campaign.');

                break;
            }
        }

        if ($this->sent > 0 || $this->failed > 0) {
            Log::info('Newsletter campaign run finished', ['sent' => $this->sent, 'failed' => $this->failed]);
        }

        return self::SUCCESS;
    }

    /**
     * Serves up to $remaining recipients of this campaign.
     *
     * Returns the recipients still allowed in this run, or -1 when the time budget is exhausted.
     */
    private function process(NewsletterCampaign $campaign, int $remaining, float $deadline): int
    {
        if (! $campaign->isSending()) {
            return $remaining;
        }

        if ($campaign->max_subscriber_id === null) {
            // Snapshot the audience once: subscribers who arrive afterwards wait for the next campaign
            $maxId = (int) (NewsletterSubscriber::whereNull('unsubscribed_at')->max('id') ?? 0);
            $recipients = NewsletterSubscriber::whereNull('unsubscribed_at')->where('id', '<=', $maxId)->count();

            $campaign->forceFill([
                'max_subscriber_id' => $maxId,
                'last_subscriber_id' => 0,
                'recipients_count' => $recipients,
                'sent_count' => 0,
                'failed_count' => 0,
                'started_at' => now(),
            ])->save();

            if ($recipients === 0) {
                $this->markSent($campaign);

                return $remaining;
            }
        }

        if (microtime(true) >= $deadline) {
            return -1;
        }

        if (! $campaign->fresh()->isSending()) {
            return $remaining;
        }

        $subscribers = NewsletterSubscriber::whereNull('unsubscribed_at')
            ->where('id', '>', (int) $campaign->last_subscriber_id)
            ->where('id', '<=', (int) $campaign->max_subscriber_id)
            ->orderBy('id')
            ->limit($remaining)
            ->get();

        if ($subscribers->isEmpty()) {
            $this->markSent($campaign);

            return $remaining;
        }

        foreach ($subscribers as $subscriber) {
            try {
                $this->mailer->send($subscriber, $campaign);
                $campaign->sent_count++;
                $this->sent++;
            } catch (Throwable $e) {
                $campaign->failed_count++;
                $this->failed++;
                Log::warning('Newsletter sending failed', [
                    'campaign' => $campaign->id,
                    'subscriber' => $subscriber->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Save after each message: a crash must not resend to the subscribers already served
            $campaign->last_subscriber_id = $subscriber->id;
            $campaign->save();

            $remaining--;

            if ($this->pause > 0) {
                usleep($this->pause * 1000);
            }

            if ($remaining === 0) {
                return 0;
            }

            if (microtime(true) >= $deadline) {
                return -1;
            }
        }

        // Fewer recipients left than the cap: the campaign is over
        if ((int) $campaign->last_subscriber_id >= (int) $campaign->max_subscriber_id) {
            $this->markSent($campaign);
        }

        return $remaining;
    }

    private function markSent(NewsletterCampaign $campaign): void
    {
        $campaign->forceFill([
            'status' => NewsletterCampaign::STATUS_SENT,
            'sent_at' => now(),
        ])->save();
    }
}
