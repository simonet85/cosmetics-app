<?php

namespace App\Http\Controllers\MoneyFusion;

use App\Models\Order;
use App\Services\OrderPayments;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

class PaymentCallbackController extends Controller
{
    public function __construct(private OrderPayments $orderPayments) {}

    /**
     * Handle payment callback/return URL
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            $token = $request->get('token') ?? $request->get('tokenPay');

            // Our own return URL names the order; use it when the gateway does not send the token back.
            if (! $token && $request->filled('order')) {
                $token = MoneyFusionPayment::where('order_id', (int) $request->get('order'))->latest('id')->value('token_pay');
            }

            Log::info('MoneyFusion Payment Callback', [
                'token' => $token,
                'all_params' => $request->all(),
            ]);

            if (! $token) {
                return view('moneyfusion.error', [
                    'message' => 'Token de paiement manquant',
                ]);
            }

            $payment = MoneyFusionPayment::where('token_pay', $token)->first();

            if (! $payment) {
                return view('moneyfusion.error', [
                    'message' => 'Paiement introuvable',
                ]);
            }

            // The query string is whatever the visitor sent: read the real status from MoneyFusion first.
            // If it cannot be reached, the local record (only ever written from verified answers) is used.
            try {
                app(MoneyFusionService::class)->checkPaymentStatus($token);
                $payment->refresh();
            } catch (\Exception $e) {
                Log::warning('MoneyFusion Callback: status check failed, using local record', ['token' => $token, 'error' => $e->getMessage()]);
            }

            // Get related order if exists
            $order = $payment->order_id ? Order::find($payment->order_id) : null;

            // Update order payment status if exists
            if ($order && $payment->isPaid()) {
                $this->orderPayments->markPaid($order);

                // Clear cart
                app(\App\Services\CartService::class)->clear();

                // Redirect to checkout success page
                return redirect($order->successUrl())
                    ->with('success', __('Votre paiement a été effectué avec succès!'));
            }

            // Rediriger selon le statut du paiement
            if ($payment->isPaid()) {
                return view('moneyfusion.success', [
                    'payment' => $payment,
                    'order' => $order,
                ]);
            } elseif ($payment->isFailed()) {
                if ($order) {
                    $this->orderPayments->markFailed($order);
                }

                return view('moneyfusion.failed', [
                    'payment' => $payment,
                    'order' => $order,
                ]);
            } else {
                if ($order) {
                    return redirect($order->pendingUrl());
                }

                return view('moneyfusion.pending', [
                    'payment' => $payment,
                    'order' => $order,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('MoneyFusion Callback Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('moneyfusion.error', [
                'message' => __('Une erreur est survenue lors du traitement de votre paiement'),
            ]);
        }
    }
}
