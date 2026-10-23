<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderPayments;
use App\Services\PaymentVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

/**
 * Reconciles MoneyFusion payments that are still "pending" locally.
 *
 * MoneyFusion's notifications are not guaranteed (no documented retries) and an abandoned payment sends
 * none at all, so this asks the gateway directly. Outcomes are applied through OrderPayments, the same
 * code the webhook and the payer's return page use, so nothing is done twice.
 */
class CheckPendingPayments extends Command
{
    protected $signature = 'moneyfusion:check-pending
        {--age=30 : Only check payments created at least this many minutes ago}
        {--limit=100 : Maximum number of payments to check in one run}
        {--expire-after=1440 : Cancel the order of a payment still pending after this many minutes (0 disables)}
        {--pause=200 : Milliseconds to wait between two gateway calls}';

    protected $description = 'Check pending MoneyFusion payments with the gateway and update their orders';

    /** @var array<string, int> */
    private array $report = [
        'checked' => 0,
        'paid' => 0,
        'failed' => 0,
        'cancelled' => 0,
        'still pending' => 0,
        'expired' => 0,
        'unverified' => 0,
        'errors' => 0,
    ];

    public function handle(PaymentVerifier $verifier, OrderPayments $orderPayments): int
    {
        $age = max(0, (int) $this->option('age'));
        $limit = max(1, (int) $this->option('limit'));
        $expireAfter = max(0, (int) $this->option('expire-after'));
        $pause = max(0, (int) $this->option('pause'));

        $payments = MoneyFusionPayment::where('statut', 'pending')
            ->where('created_at', '<=', now()->subMinutes($age))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No pending payment to check.');

            return self::SUCCESS;
        }

        $this->info("Checking {$payments->count()} pending payment(s) older than {$age} minute(s)...");

        $this->withProgressBar($payments, function (MoneyFusionPayment $payment) use ($verifier, $orderPayments, $expireAfter, $pause) {
            $this->check($payment, $verifier, $orderPayments, $expireAfter);

            if ($pause > 0) {
                usleep($pause * 1000);
            }
        });

        $this->newLine(2);
        $this->table(['Result', 'Payments'], collect($this->report)->map(fn ($count, $label) => [$label, $count])->values());

        Log::info('MoneyFusion check-pending finished', $this->report);

        return self::SUCCESS;
    }

    private function check(MoneyFusionPayment $payment, PaymentVerifier $verifier, OrderPayments $orderPayments, int $expireAfter): void
    {
        $this->report['checked']++;

        // Without a real answer from the API the local record is all there is, and it proves nothing.
        match ($outcome = $verifier->verify($payment)) {
            PaymentVerifier::PAID => $this->report['paid']++,
            PaymentVerifier::FAILED => $this->report['failed']++,
            PaymentVerifier::CANCELLED => $this->report['cancelled']++,
            PaymentVerifier::UNVERIFIED => $this->report['unverified']++,
            PaymentVerifier::ERROR => $this->report['errors']++,
            default => $this->expireIfAbandoned($payment, $payment->order_id ? Order::find($payment->order_id) : null, $orderPayments, $expireAfter),
        };
    }

    /**
     * Still pending at the gateway. After the expiry delay the session is considered abandoned: the order is
     * cancelled and its stock released. If the payer does pay later, the webhook or the return page revives it.
     */
    private function expireIfAbandoned(MoneyFusionPayment $payment, ?Order $order, OrderPayments $orderPayments, int $expireAfter): void
    {
        if ($expireAfter === 0 || $payment->created_at->gt(now()->subMinutes($expireAfter))) {
            $this->report['still pending']++;

            return;
        }

        $payment->update(['statut' => 'cancelled']);
        $order && $orderPayments->markCancelled($order);
        $this->report['expired']++;
    }
}
