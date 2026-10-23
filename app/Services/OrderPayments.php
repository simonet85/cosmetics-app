<?php

namespace App\Services;

use App\Mail\OrderPaid;
use App\Mail\PaymentFailed;
use App\Mail\PaymentPending;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Applies a verified payment outcome to an order.
 *
 * Used by both the MoneyFusion webhook and the payer's return callback. Whichever arrives first does
 * the work; the other finds nothing left to change, so the confirmation email is sent exactly once.
 * Each transition is a single conditional UPDATE, so two simultaneous requests cannot both win.
 */
class OrderPayments
{
    public function markPaid(Order $order): void
    {
        $before = Order::whereKey($order->id)->first(['status', 'payment_status']);

        $changed = Order::whereKey($order->id)
            ->where('payment_status', '!=', 'paid')
            ->update(['payment_status' => 'paid']);

        if (! $changed) {
            return;
        }

        if ($before?->status === 'cancelled') {
            // The gateway had failed or cancelled this payment (the order was cancelled for it) but the
            // customer then paid: the payment wins, so the order comes back and takes its stock again.
            // A cancellation that was not caused by the payment (customer or admin) stays cancelled.
            if ($before->payment_status !== 'failed') {
                Log::error('Payment received for an order cancelled by the customer or an admin: refund needed', ['order_id' => $order->id]);

                return;
            }

            Order::whereKey($order->id)->update(['status' => 'processing']);
            $order->reserveStock();
        } else {
            // Only move a fresh order forward; never rewind a shipped one.
            Order::whereKey($order->id)->where('status', 'pending')->update(['status' => 'processing']);
        }

        $order->refresh();

        $this->send($order, new OrderPaid($order), 'Order confirmation');
    }

    /**
     * The payment failed. The customer's "retry" starts a new checkout (a new order), so this order is
     * abandoned: cancel it, give its stock back and tell the customer. A later successful payment revives
     * it (markPaid).
     */
    public function markFailed(Order $order): void
    {
        $this->cancelUnpaid($order, 'failed');
    }

    /**
     * The customer abandoned or the gateway cancelled the session. orders.payment_status has no
     * "cancelled" value, so the order is cancelled and its payment marked failed.
     */
    public function markCancelled(Order $order): void
    {
        $this->cancelUnpaid($order, 'cancelled');
    }

    /** The payment never completed within the allowed time (scheduled check). */
    public function markExpired(Order $order): void
    {
        $this->cancelUnpaid($order, 'expired');
    }

    /**
     * "Your payment is still pending" reminder, sent once per order, only while the order really is waiting.
     * Returns whether a reminder was sent.
     */
    public function remindPending(Order $order, ?int $expiresInHours = null): bool
    {
        $claimed = Order::whereKey($order->id)
            ->whereNull('payment_reminder_sent_at')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->update(['payment_reminder_sent_at' => now()]);

        if (! $claimed) {
            return false;
        }

        $order->refresh();

        $this->send($order, new PaymentPending($order, $expiresInHours), 'Pending payment reminder');

        return true;
    }

    private function cancelUnpaid(Order $order, string $reason): void
    {
        $changed = Order::whereKey($order->id)
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'failed', 'status' => 'cancelled']);

        if (! $changed) {
            return;
        }

        $order->releaseStock();
        $order->refresh();

        $this->send($order, new PaymentFailed($order, $reason), 'Payment failed');
    }

    /** The outcome is already recorded; a mail failure must not make the gateway retry or hide it. */
    private function send(Order $order, object $mailable, string $what): void
    {
        try {
            Mail::to($order->customer_email)->send($mailable);

            Log::info("{$what} email sent", ['order_id' => $order->id, 'order_number' => $order->order_number]);
        } catch (\Exception $e) {
            Log::error("Failed to send {$what} email", ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
