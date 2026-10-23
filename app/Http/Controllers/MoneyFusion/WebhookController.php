<?php

namespace App\Http\Controllers\MoneyFusion;

use App\Models\Order;
use App\Services\OrderPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Simonet85\LaravelMoneyFusion\Http\Controllers\WebhookController as PackageWebhookController;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

/**
 * MoneyFusion does not sign its notifications, so the request body is only a signal: the package's
 * handle() reads the real status from MoneyFusion's API before changing anything. This subclass only
 * says what a verified outcome means for our orders.
 */
class WebhookController extends PackageWebhookController
{
    public function __construct(private OrderPayments $orderPayments) {}

    public function handle(Request $request, MoneyFusionService $moneyFusion): JsonResponse
    {
        // Older notifications used "token" instead of "tokenPay"
        if (! $request->filled('tokenPay') && $request->filled('token')) {
            $request->merge(['tokenPay' => $request->input('token')]);
        }

        // The package returns early for an already-paid payment. If the order update was lost the first
        // time (e.g. a database error after the payment was recorded), repair it on the retry.
        $payment = MoneyFusionPayment::where('token_pay', (string) $request->input('tokenPay'))->first();

        if ($payment?->isPaid() && $payment->order_id) {
            $this->postPaymentSuccess($payment, []);
        }

        return parent::handle($request, $moneyFusion);
    }

    protected function dispatchEvents(MoneyFusionPayment $payment, string $oldStatus, array $data): void
    {
        parent::dispatchEvents($payment, $oldStatus, $data);

        if ($oldStatus !== $payment->statut && $payment->statut === 'cancelled') {
            $this->withOrder($payment, fn (Order $order) => $this->orderPayments->markCancelled($order));
        }
    }

    protected function postPaymentSuccess(MoneyFusionPayment $payment, array $data): void
    {
        $this->withOrder($payment, fn (Order $order) => $this->orderPayments->markPaid($order));
    }

    protected function postPaymentFailure(MoneyFusionPayment $payment, array $data): void
    {
        $this->withOrder($payment, fn (Order $order) => $this->orderPayments->markFailed($order));
    }

    private function withOrder(MoneyFusionPayment $payment, callable $apply): void
    {
        $order = $payment->order_id ? Order::find($payment->order_id) : null;

        if ($order) {
            $apply($order);
        }
    }
}
