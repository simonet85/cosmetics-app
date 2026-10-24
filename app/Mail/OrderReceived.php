<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when an order is registered but not paid yet (cash on delivery): the order is recorded, here is what
 * was ordered and what happens next. Paid orders get OrderPaid (with the invoice) instead.
 */
class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre commande '.$this->order->order_number.' est enregistrée - Glowing Cosmetics',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-received', with: [
            'orderUrl' => $this->order->successUrl(),
        ]);
    }
}
