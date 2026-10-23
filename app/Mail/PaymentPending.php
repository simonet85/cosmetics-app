<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reminder: the payment has been pending for a while. $expiresInHours is when the order will be cancelled
 * if nothing happens (null when automatic cancellation is off).
 */
class PaymentPending extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?int $expiresInHours = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paiement en attente de confirmation - commande '.$this->order->order_number.' - Glowing Cosmetics',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-pending',
            with: ['checkUrl' => $this->order->pendingUrl(), 'shopUrl' => route('shop.index')],
        );
    }
}
