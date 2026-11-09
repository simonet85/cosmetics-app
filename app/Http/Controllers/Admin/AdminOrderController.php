<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderFilterRequest;
use App\Models\Order;
use App\Queries\OrderListQuery;
use App\Services\InvoicePdf;
use App\Services\OrderWorkflow;
use App\Services\PaymentVerifier;
use App\Support\OrderLabels;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(OrderFilterRequest $request, OrderListQuery $orders)
    {
        $filters = $request->filters();

        return view('admin.orders.index', [
            'orders' => $orders->paginate($filters),
            'filters' => $filters,
            'statuses' => OrderLabels::statuses(),
            'payments' => OrderLabels::payments(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product.primaryImage', 'moneyFusionPayment']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order, OrderWorkflow $workflow)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(OrderLabels::statuses()))],
            'tracking_number' => 'nullable|string|max:255',
            'shipping_carrier' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url:http,https|max:500',
        ]);

        // The dedicated tracking form always posts all three fields (an empty one clears it); the
        // status form does not post them, so a status change alone leaves the tracking untouched.
        $tracking = $request->hasAny(['tracking_number', 'shipping_carrier', 'tracking_url'])
            ? [
                'tracking_number' => $data['tracking_number'] ?? null,
                'shipping_carrier' => $data['shipping_carrier'] ?? null,
                'tracking_url' => $data['tracking_url'] ?? null,
            ]
            : null;

        $result = $workflow->changeStatus($order, $data['status'], $tracking);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updatePaymentStatus(Request $request, Order $order, OrderWorkflow $workflow)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'in:'.implode(',', array_keys(OrderLabels::payments()))],
        ]);

        $result = $workflow->changePaymentStatus($order, $data['payment_status']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function invoice(Order $order, InvoicePdf $pdf)
    {
        return $pdf->make($order)->download('facture-'.$order->order_number.'.pdf');
    }

    /**
     * Ask MoneyFusion for the real status of this order's payment and apply it.
     */
    public function checkPayment(Order $order, PaymentVerifier $verifier)
    {
        $outcome = $order->payment_method === 'moneyfusion' ? $verifier->verifyOrder($order) : null;

        $order->refresh();

        return match ($outcome) {
            null => back()->with('error', 'Aucun paiement MoneyFusion à vérifier pour cette commande.'),
            PaymentVerifier::PAID => back()->with('success', 'Paiement confirmé par MoneyFusion : la commande est marquée payée.'),
            PaymentVerifier::FAILED => back()->with('success', 'MoneyFusion indique que le paiement a échoué : la commande est annulée et son stock remis en vente.'),
            PaymentVerifier::CANCELLED => back()->with('success', 'MoneyFusion indique que le paiement a été annulé : la commande est annulée et son stock remis en vente.'),
            PaymentVerifier::PENDING => back()->with('success', 'Le paiement est toujours en attente chez MoneyFusion. Aucun changement.'),
            default => back()->with('error', 'Impossible de joindre MoneyFusion pour le moment. Réessayez dans quelques instants.'),
        };
    }
}
