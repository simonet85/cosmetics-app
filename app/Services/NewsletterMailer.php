<?php

namespace App\Services;

use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

/**
 * Sends one campaign message. Injectable so the sending command can be tested for partial failures
 * without touching the real transport.
 */
class NewsletterMailer
{
    public function send(NewsletterSubscriber $subscriber, NewsletterCampaign $campaign): void
    {
        Mail::to($subscriber->email)->send(new NewsletterCampaignMail($campaign, $subscriber));
    }
}
