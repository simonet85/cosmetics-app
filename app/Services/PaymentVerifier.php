<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

/**
 * Asks MoneyFusion for the real status of a payment and applies a verified outcome to its order.
 *
 * Shared by everything that checks on demand: the scheduled command, the customer's "check now" and
 * pending page, and the admin's "check payment" button. A local record that was not confirmed by the
 * gateway proves nothing, so it is never used to mark an order paid here.
 */
class PaymentVerifier
{
    public const PAID = 'paid';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    public const PENDING = 'pending';

    /** The gateway could not be reached, or answered with data that is not its own. */
    public const UNVERIFIED = 'unverified';

    public const ERROR = 'error';

    public function __construct(private OrderPayments $orderPayments) {}

    public function verify(MoneyFusionPayment $payment): string
    {
        try {
            // Resolved here: the service throws when MoneyFusion is not configured
            $answer = app(MoneyFusionService::class)->checkPaymentStatus($payment->token_pay);
        } catch (\Throwable $e) {
            Log::warning('MoneyFusion verify: check failed', ['token' => $payment->token_pay, 'error' => $e->getMessage()]);

            return self::ERROR;
        }

        if (! ($answer['verified'] ?? false)) {
            return self::UNVERIFIED;
        }

        $payment->refresh();

        return $this->apply($payment, $payment->order_id ? Order::find($payment->order_id) : null);
    }

    /**
     * Verify the latest MoneyFusion payment of an order. Returns null when the order has none.
     * A payment that is already paid is not asked about again; for the others, $minSecondsBetweenChecks
     * limits how often the gateway is actually called (a page polling every few seconds must not hammer it).
     */
    public function verifyOrder(Order $order, int $minSecondsBetweenChecks = 0): ?string
    {
        $payment = MoneyFusionPayment::where('order_id', $order->id)->latest('id')->first();

        if (! $payment) {
            return null;
        }

        if ($payment->isPaid()) {
            return $this->apply($payment, $order);
        }

        if ($minSecondsBetweenChecks > 0 && ! Cache::add("moneyfusion-check:{$payment->id}", true, $minSecondsBetweenChecks)) {
            return $this->outcomeOf($payment);
        }

        return $this->verify($payment);
    }

    /** Make the order reflect the (verified) state of the payment. Idempotent. */
    public function apply(MoneyFusionPayment $payment, ?Order $order): string
    {
        $outcome = $this->outcomeOf($payment);

        if ($order) {
            match ($outcome) {
                self::PAID => $this->orderPayments->markPaid($order),
                self::FAILED => $this->orderPayments->markFailed($order),
                self::CANCELLED => $this->orderPayments->markCancelled($order),
                default => null,
            };
        }

        return $outcome;
    }

    private function outcomeOf(MoneyFusionPayment $payment): string
    {
        return match ($payment->statut) {
            'paid' => self::PAID,
            'failed' => self::FAILED,
            'cancelled' => self::CANCELLED,
            default => self::PENDING,
        };
    }
}
