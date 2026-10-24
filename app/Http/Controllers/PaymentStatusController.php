<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    /** Seconds during which a recent check is reused instead of asking MoneyFusion again. */
    private const MIN_SECONDS_BETWEEN_CHECKS = 10;

    /**
     * Current payment state of an order, for the pending page and the order page.
     * Owner or signed link only. When the payment is still pending it asks the gateway (rate limited).
     */
    public function show(Request $request, Order $order, PaymentVerifier $verifier): JsonResponse
    {
        abort_unless($order->isViewableBy($request->user(), $request), 403);

        $outcome = $order->payment_method === 'moneyfusion'
            ? $verifier->verifyOrder($order, self::MIN_SECONDS_BETWEEN_CHECKS)
            : null;

        $order->refresh();

        // The gateway could not be asked: say so, but do not claim a failure
        $state = match (true) {
            $order->payment_status === 'paid' => 'paid',
            in_array($outcome, [PaymentVerifier::UNVERIFIED, PaymentVerifier::ERROR], true) => 'unavailable',
            $order->status === 'cancelled' || in_array($order->payment_status, ['failed', 'refunded'], true) => 'failed',
            default => 'pending',
        };

        return response()->json([
            'state' => $state,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'message' => match ($state) {
                'paid' => __('Paiement confirmé. Merci !'),
                'failed' => __('Le paiement n\'a pas abouti.'),
                'unavailable' => __('Vérification momentanément indisponible, nouvel essai automatique.'),
                default => __('Paiement toujours en attente de confirmation.'),
            },
            'redirect' => $state === 'paid' ? $order->successUrl() : null,
            'checked_at' => now()->format('H:i:s'),
        ]);
    }
}
