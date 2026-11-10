<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The shop owner's answer to a message sent through the public contact form. */
class ContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $replyText,
        public string $replySubject,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->replySubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-reply');
    }
}
