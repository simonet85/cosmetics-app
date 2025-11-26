<?php

namespace App\Services;

use Simonet85\LaravelMoneyFusion\MoneyFusionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CustomMoneyFusionService extends MoneyFusionService
{
    protected bool $verifySSL;
    protected ?string $checkPaymentUrl;

    public function __construct()
    {
        parent::__construct();
        $this->verifySSL = config('moneyfusion.verify_ssl', true);
        $this->checkPaymentUrl = config('moneyfusion.check_payment_url');
    }

    /**
     * Créer un paiement avec gestion SSL
     */
    public function createPayment(array $data): array
    {
        try {
            $payload = $this->preparePayload($data);

            Log::info('MoneyFusion: Creating payment', ['payload' => $payload]);

            $response = Http::timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySSL,
                ])
                ->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                throw new Exception('API error: ' . $response->body());
            }

            $result = $response->json();

            if (!($result['statut'] ?? false)) {
                throw new Exception('Payment creation failed: ' . ($result['message'] ?? 'Unknown error'));
            }

            // Sauvegarder en base
            $this->storePayment($data, $result);

            return $result;

        } catch (\Exception $e) {
            Log::error('MoneyFusion: Payment creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new Exception($e->getMessage(), 0, $e);
        }
    }

    /**
     * Vérifier le statut d'un paiement avec gestion SSL
     */
    public function checkPaymentStatus(string $tokenPay): array
    {
        try {
            // Si une URL de vérification spécifique est configurée, l'utiliser
            if ($this->checkPaymentUrl) {
                $url = rtrim($this->checkPaymentUrl, '/') . '/' . $tokenPay;
            } else {
                // Sinon, essayer de construire l'URL à partir de l'URL de création
                $baseUrl = rtrim($this->apiUrl, '/');
                $url = str_replace('/pay', "/check/{$tokenPay}", $baseUrl);
            }

            Log::info('MoneyFusion: Checking payment status', ['url' => $url, 'token' => $tokenPay]);

            $response = Http::timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySSL,
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('MoneyFusion: Check payment API returned error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Si l'API ne supporte pas la vérification en ligne, retourner les données locales
                $payment = $this->getPaymentByToken($tokenPay);
                if ($payment) {
                    return [
                        'statut' => true,
                        'data' => [
                            'statut' => $payment->statut,
                            'montant' => $payment->montant,
                            'token' => $payment->token_pay,
                            'source' => 'local_database',
                            'message' => 'Vérification en ligne non disponible. Données de la base de données locale.'
                        ]
                    ];
                }

                throw new Exception('API error: ' . $response->body());
            }

            $result = $response->json();

            // Mettre à jour en base
            if (isset($result['data'])) {
                $this->updatePaymentStatus($tokenPay, $result['data']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('MoneyFusion: Status check failed', [
                'error' => $e->getMessage(),
                'token' => $tokenPay
            ]);
            throw new Exception($e->getMessage(), 0, $e);
        }
    }
}
