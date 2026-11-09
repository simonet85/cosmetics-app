<?php

namespace App\Services;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Support\OrderLabels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Admin-side order transitions, shared by the order page and the bulk actions.
 *
 * Order status changes keep their stock and notification effects; payment changes go through
 * OrderPayments, so a payment marked paid by hand sends the same confirmation (with invoice)
 * as a gateway payment. Refusals are returned, never thrown: a bulk action reports them.
 */
class OrderWorkflow
{
    public function __construct(private OrderPayments $payments) {}

    /**
     * @param  array{tracking_number?: ?string, shipping_carrier?: ?string, tracking_url?: ?string}|null  $tracking
     *                                                                                                            When given, the three tracking columns are replaced (empty values clear them).
     * @return array{ok: bool, message: string}
     */
    public function changeStatus(Order $order, string $status, ?array $tracking = null): array
    {
        if (! array_key_exists($status, OrderLabels::statuses())) {
            return ['ok' => false, 'message' => 'Statut de commande inconnu.'];
        }

        $oldStatus = $order->status;

        $update = ['status' => $status];

        if ($tracking !== null) {
            $update += [
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'shipping_carrier' => $tracking['shipping_carrier'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ];
        }

        $order->update($update);

        // Cancelling gives the stock back; reopening a cancelled order takes it again
        if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
            $order->releaseStock();
        } elseif ($oldStatus === 'cancelled' && $status !== 'cancelled') {
            $order->reserveStock();
        }

        // The shipping notification can be switched off in the settings; other status mails are unaffected
        $notify = $status !== 'shipped' || getSetting('send_shipping_notification', '1') === '1';

        if ($oldStatus !== $status && $notify) {
            $this->sendStatusMail($order, $oldStatus, $status);
        }

        return ['ok' => true, 'message' => 'Statut de la commande mis à jour avec succès.'];
    }

    /**
     * Marking a payment paid goes through OrderPayments (confirmation mail, processing, stock revival);
     * a failure cancels the order as a gateway failure would. Other values are bookkeeping only.
     *
     * @return array{ok: bool, message: string}
     */
    public function changePaymentStatus(Order $order, string $status): array
    {
        if (! array_key_exists($status, OrderLabels::payments())) {
            return ['ok' => false, 'message' => 'Statut de paiement inconnu.'];
        }

        if ($status === 'paid') {
            if ($order->payment_status === 'paid') {
                return ['ok' => false, 'message' => 'Cette commande est déjà marquée payée.'];
            }

            $this->payments->markPaid($order);
            $order->refresh();

            if ($order->payment_status !== 'paid') {
                return ['ok' => false, 'message' => "Le paiement n'a pas pu être enregistré pour cette commande."];
            }

            if ($order->status === 'cancelled') {
                // OrderPayments refuses to revive an order cancelled by an admin or the customer,
                // because the money arriving does not prove what should happen to that order
                return ['ok' => true, 'message' => 'Paiement enregistré, mais la commande reste annulée : un remboursement est peut-être nécessaire.'];
            }

            return ['ok' => true, 'message' => 'Paiement enregistré : la commande est marquée payée et le client prévenu.'];
        }

        if ($status === 'failed') {
            if ($order->payment_status === 'paid') {
                return ['ok' => false, 'message' => 'Cette commande est déjà payée : remboursez-la plutôt que de marquer le paiement en échec.'];
            }

            if ($order->payment_status === 'failed') {
                return ['ok' => false, 'message' => 'Le paiement de cette commande est déjà marqué en échec.'];
            }

            $this->payments->markFailed($order);

            return ['ok' => true, 'message' => "Paiement marqué en échec : la commande est annulée et son stock remis en vente."];
        }

        // pending / refunded: the money moved outside the shop (cash, bank), so only the record changes.
        // A refund never touches the order status or the stock.
        if ($order->payment_status === $status) {
            return ['ok' => false, 'message' => 'Le statut de paiement est déjà à jour.'];
        }

        $old = $order->payment_status;
        $order->update(['payment_status' => $status]);

        Log::warning('Order payment status changed by hand, outside the gateway flow', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'from' => $old,
            'to' => $status,
            'admin_id' => auth()->id(),
        ]);

        return ['ok' => true, 'message' => 'Statut de paiement mis à jour.'];
    }

    private function sendStatusMail(Order $order, string $oldStatus, string $newStatus): void
    {
        try {
            Mail::to($order->customer_email)->send(new OrderStatusUpdated($order, $oldStatus));

            Log::info('Order status update email sent', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'email' => $order->customer_email,
            ]);
        } catch (\Exception $emailError) {
            Log::error('Failed to send order status update email', [
                'order_id' => $order->id,
                'error' => $emailError->getMessage(),
            ]);
            // Don't fail the status update if email fails
        }
    }
}
