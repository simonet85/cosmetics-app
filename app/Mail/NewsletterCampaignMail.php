<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/** One newsletter campaign, personalised only by its unsubscribe link. */
class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterCampaign $campaign,
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->campaign->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.newsletter');
    }

    /** The header mailbox providers use to offer a one-click unsubscribe without opening the message. */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.route('newsletter.unsubscribe', $this->subscriber->token).'>',
        ]);
    }
}
