<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderFilterRequest;
use App\Queries\OrderListQuery;
use App\Support\OrderLabels;
use App\Support\PaymentMethods;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Downloads the filtered order list as a spreadsheet-friendly CSV (Excel FR: ";" separator, BOM). */
class AdminOrderExportController extends Controller
{
    public function __invoke(OrderFilterRequest $request, OrderListQuery $orders): StreamedResponse
    {
        $query = $orders->build($request->filters())->reorder()->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            $this->line($out, [
                'N° commande', 'Date', 'Client', 'Email', 'Téléphone', 'Total (FCFA)',
                'Statut', 'Paiement', 'Moyen', 'Transaction', 'Transporteur', 'N° de suivi', 'Lien de suivi',
            ]);

            $query->with(['user', 'moneyFusionPayment'])->chunkById(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    $payment = $order->moneyFusionPayment;
                    $total = (int) round((float) $order->total);

                    $this->line($out, [
                        $order->order_number,
                        $order->created_at?->format('d/m/Y H:i'),
                        $order->customerName(),
                        $order->customer_email,
                        $order->customer_phone,
                        $total,
                        OrderLabels::status($order->status),
                        OrderLabels::payment($order->payment_status),
                        PaymentMethods::label($order->payment_method),
                        $payment?->numero_transaction,
                        $order->shipping_carrier,
                        $order->tracking_number,
                        $order->tracking_url,
                    ]);
                }
            });

            fclose($out);
        }, 'commandes-'.now()->format('Y-m-d-Hi').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * A cell that starts with "=", "+", "-" or "@" would run as a formula in Excel/LibreOffice.
     * Order numbers, names and notes are customer-provided, so they are neutralized with a leading quote.
     *
     * @param  resource  $out
     * @param  array<int, mixed>  $row
     */
    private function line($out, array $row): void
    {
        fputcsv($out, array_map(fn ($value) => $this->cell($value), $row), ';', '"', '');
    }

    private function cell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
