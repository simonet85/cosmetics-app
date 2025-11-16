<?php

namespace App\Http\Controllers\MoneyFusion;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use App\Models\Order;

class PaymentCallbackController extends Controller
{
    /**
     * Handle payment callback/return URL
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        try {
            $token = $request->get('token') ?? $request->get('tokenPay');

            Log::info('MoneyFusion Payment Callback', [
                'token' => $token,
                'all_params' => $request->all()
            ]);

            if (!$token) {
                return view('moneyfusion.error', [
                    'message' => 'Token de paiement manquant'
                ]);
            }

            $payment = MoneyFusionPayment::where('token_pay', $token)->first();

            if (!$payment) {
                return view('moneyfusion.error', [
                    'message' => 'Paiement introuvable'
                ]);
            }

            // Get related order if exists
            $order = $payment->order_id ? Order::find($payment->order_id) : null;

            // Update order payment status if exists
            if ($order && $payment->isPaid()) {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);

                // Clear cart
                session()->forget('cart');

                // Redirect to checkout success page
                return redirect()->route('checkout.success', $order->id)
                    ->with('success', 'Votre paiement a été effectué avec succès!');
            }

            // Rediriger selon le statut du paiement
            if ($payment->isPaid()) {
                return view('moneyfusion.success', [
                    'payment' => $payment,
                    'order' => $order
                ]);
            } elseif ($payment->isFailed()) {
                if ($order) {
                    $order->update(['payment_status' => 'failed']);
                }
                return view('moneyfusion.failed', [
                    'payment' => $payment,
                    'order' => $order
                ]);
            } else {
                return view('moneyfusion.pending', [
                    'payment' => $payment,
                    'order' => $order
                ]);
            }

        } catch (\Exception $e) {
            Log::error('MoneyFusion Callback Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('moneyfusion.error', [
                'message' => 'Une erreur est survenue lors du traitement de votre paiement'
            ]);
        }
    }
}
