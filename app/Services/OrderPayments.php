<?php

namespace App\Services;

use App\Mail\OrderPaid;
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

        try {
            Mail::to($order->customer_email)->send(new OrderPaid($order));

            Log::info('Order confirmation email sent', ['order_id' => $order->id, 'order_number' => $order->order_number]);
        } catch (\Exception $e) {
            // The payment is recorded; a mail failure must not make the gateway retry or hide it.
            Log::error('Failed to send order confirmation email', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The payment failed. The customer's "retry" starts a new checkout (a new order), so this order is
     * abandoned: cancel it and give its stock back. A later successful payment revives it (markPaid).
     */
    public function markFailed(Order $order): void
    {
        $this->cancelUnpaid($order);
    }

    /**
     * The customer abandoned or the gateway cancelled the session. orders.payment_status has no
     * "cancelled" value, so the order is cancelled and its payment marked failed.
     */
    public function markCancelled(Order $order): void
    {
        $this->cancelUnpaid($order);
    }

    private function cancelUnpaid(Order $order): void
    {
        $changed = Order::whereKey($order->id)
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'failed', 'status' => 'cancelled']);

        if ($changed) {
            $order->releaseStock();
        }
    }
}
