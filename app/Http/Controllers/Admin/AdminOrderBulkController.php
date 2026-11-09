<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use App\Services\PaymentVerifier;
use App\Support\OrderLabels;
use Illuminate\Http\Request;

/** Actions on a selection of orders from the list. Each one reports what it did, per order. */
class AdminOrderBulkController extends Controller
{
    public function updateStatus(Request $request, OrderWorkflow $workflow)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:25'],
            'ids.*' => ['integer'],
            'status' => ['required', 'in:'.implode(',', array_keys(OrderLabels::statuses()))],
        ]);

        $changed = 0;
        $requested = count(array_unique($data['ids']));

        foreach (Order::whereIn('id', array_unique($data['ids']))->get() as $order) {
            if ($workflow->changeStatus($order, $data['status'])['ok']) {
                $changed++;
            }
        }

        // Ids that no longer exist (a parallel deletion, a stale page) simply drop out of the count
        $ignored = $requested - $changed;

        return back()->with(
            $changed > 0 ? 'success' : 'error',
            "{$changed} commande(s) mise(s) à jour.".($ignored > 0 ? " {$ignored} ignorée(s) (introuvable(s))." : '')
        );
    }

    public function checkPayments(Request $request, PaymentVerifier $verifier)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['integer'],
        ]);

        $orders = Order::whereIn('id', array_unique($data['ids']))
            ->where('payment_method', 'moneyfusion')
            ->where('payment_status', '!=', 'paid')
            ->get();

        $counts = [
            PaymentVerifier::PAID => 0,
            PaymentVerifier::FAILED => 0,
            PaymentVerifier::CANCELLED => 0,
            PaymentVerifier::PENDING => 0,
            'unreachable' => 0,
        ];

        // The gateway is rate limited: a configurable pause keeps a batch from being treated as an attack
        $pause = max(0, (int) getSetting('moneyfusion_bulk_check_pause_ms', '250'));

        foreach ($orders as $index => $order) {
            if ($index > 0 && $pause > 0) {
                usleep($pause * 1000);
            }

            $outcome = $verifier->verifyOrder($order);

            match ($outcome) {
                PaymentVerifier::PAID => $counts[PaymentVerifier::PAID]++,
                PaymentVerifier::FAILED => $counts[PaymentVerifier::FAILED]++,
                PaymentVerifier::CANCELLED => $counts[PaymentVerifier::CANCELLED]++,
                PaymentVerifier::PENDING => $counts[PaymentVerifier::PENDING]++,
                default => $counts['unreachable']++,
            };
        }

        if ($orders->isEmpty()) {
            return back()->with('error', "Aucune des commandes sélectionnées n'a de paiement MoneyFusion à vérifier.");
        }

        $summary = "{$orders->count()} paiement(s) vérifié(s) : "
            ."{$counts[PaymentVerifier::PAID]} payé(s), "
            .($counts[PaymentVerifier::FAILED] + $counts[PaymentVerifier::CANCELLED])." échoué(s)/annulé(s), "
            ."{$counts[PaymentVerifier::PENDING]} toujours en attente";

        if ($counts['unreachable'] > 0) {
            $summary .= ", {$counts['unreachable']} non vérifiable(s) (MoneyFusion injoignable)";
        }

        return back()->with('success', $summary.'.');
    }
}
