<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The payment did not go through and the order was cancelled.
 * $reason is "failed" (declined), "cancelled" (the payer backed out) or "expired" (never completed).
 */
class PaymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $reason = 'failed') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre paiement n\'a pas abouti - commande '.$this->order->order_number.' - Glowing Cosmetics',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
            with: ['retryUrl' => route('checkout.index'), 'shopUrl' => route('shop.index')],
        );
    }
}
