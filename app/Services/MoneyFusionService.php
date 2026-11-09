<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Simonet85\LaravelMoneyFusion\MoneyFusionService as PackageMoneyFusionService;

/**
 * MoneyFusion reports "Montant" net of its own fees ("frais" holds them) while we store the gross
 * total charged to the customer: the package compares them directly, so a paid session that carries
 * fees (Wave, for instance) never got credited. Compare the gross (Montant + frais) instead.
 */
class MoneyFusionService extends PackageMoneyFusionService
{
    protected function updatePaymentStatus(string $tokenPay, array $data): void
    {
        $payment = MoneyFusionPayment::where('token_pay', $tokenPay)->first();

        if (! $payment) {
            return;
        }

        $status = self::normalizeStatus($data['statut'] ?? null);

        if ($payment->isPaid() && $status !== 'paid') {
            return;
        }

        if ($status === 'paid' && isset($data['Montant'])) {
            $paid = (float) $data['Montant'] + (float) ($data['frais'] ?? 0);

            if (abs($paid - (float) $payment->montant) >= 0.01) {
                Log::error('MoneyFusion: amount mismatch, payment not marked as paid', [
                    'token' => $tokenPay,
                    'expected' => $payment->montant,
                    'received' => $data['Montant'],
                    'fees' => $data['frais'] ?? 0,
                ]);

                return;
            }
        }

        $updateData = [
            'statut' => $status,
            'numero_transaction' => $data['numeroTransaction'] ?? $payment->numero_transaction,
            'moyen' => $data['moyen'] ?? $payment->moyen,
            'frais' => $data['frais'] ?? $payment->frais ?? 0,
            'raw_response' => $data,
        ];

        if ($status === 'paid' && ! $payment->paid_at) {
            $updateData['paid_at'] = now();
        }

        $payment->update($updateData);
    }
}
