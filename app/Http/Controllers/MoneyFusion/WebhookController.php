<?php

namespace App\Http\Controllers\MoneyFusion;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use App\Models\Order;
use App\Mail\OrderPaid;

class WebhookController extends Controller
{
    /**
     * Handle MoneyFusion webhook notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('MoneyFusion Webhook received', ['data' => $request->all()]);

            $data = $request->all();

            // Support both 'token' and 'tokenPay' field names
            $token = $data['token'] ?? $data['tokenPay'] ?? null;

            // Valider les données du webhook
            if (!$token) {
                Log::warning('MoneyFusion Webhook: Missing token', ['data' => $data]);
                return response()->json(['error' => 'Missing token'], 400);
            }
            $payment = MoneyFusionPayment::where('token_pay', $token)->first();

            if (!$payment) {
                Log::warning('MoneyFusion Webhook: Payment not found', ['token' => $token]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Mettre à jour le statut du paiement
            $updateData = [
                'statut' => $data['statut'] ?? 'pending',
                'numero_transaction' => $data['numeroTransaction'] ?? null,
                'moyen' => $data['moyen'] ?? null,
                'frais' => $data['frais'] ?? 0,
                'raw_response' => $data,
            ];

            // Si le paiement est réussi, enregistrer la date
            if (($data['statut'] ?? '') === 'paid' && !$payment->paid_at) {
                $updateData['paid_at'] = now();
            }

            $payment->update($updateData);

            // Update related order if exists
            if ($payment->order_id) {
                $order = Order::find($payment->order_id);
                if ($order) {
                    if (($data['statut'] ?? '') === 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                        ]);

                        // Send order confirmation email with invoice
                        try {
                            Mail::to($order->customer_email)->send(new OrderPaid($order));
                            Log::info('Order confirmation email sent', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'email' => $order->customer_email
                            ]);
                        } catch (\Exception $emailError) {
                            Log::error('Failed to send order confirmation email', [
                                'order_id' => $order->id,
                                'error' => $emailError->getMessage()
                            ]);
                        }
                    } elseif (($data['statut'] ?? '') === 'failed') {
                        $order->update(['payment_status' => 'failed']);
                    } elseif (($data['statut'] ?? '') === 'cancelled') {
                        $order->update([
                            'payment_status' => 'cancelled',
                            'status' => 'cancelled'
                        ]);
                    }
                }
            }

            Log::info('MoneyFusion Webhook: Payment updated', [
                'token' => $token,
                'status' => $data['statut'] ?? 'unknown',
                'order_id' => $payment->order_id
            ]);

            // Déclencher un événement si nécessaire
            // event(new PaymentStatusUpdated($payment));

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('MoneyFusion Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
