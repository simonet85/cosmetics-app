# Vérification du Statut des Paiements MoneyFusion

## Table des matières

1. [Configuration](#configuration)
2. [Utilisation de base](#utilisation-de-base)
3. [Cas d'usage pratiques](#cas-dusage-pratiques)
4. [Commande Artisan pour vérification automatique](#commande-artisan-pour-vérification-automatique)
5. [Interface admin pour vérification manuelle](#interface-admin-pour-vérification-manuelle)
6. [Job pour vérification en arrière-plan](#job-pour-vérification-en-arrière-plan)
7. [API endpoint pour vérification](#api-endpoint-pour-vérification)

---

## Configuration

### Variables d'environnement

Dans votre fichier `.env`, ajoutez :

```env
# URL de vérification des paiements MoneyFusion
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif
```

### Configuration déjà présente

Le fichier [config/moneyfusion.php:24](config/moneyfusion.php#L24) contient déjà :

```php
'check_payment_url' => env('MONEYFUSION_CHECK_PAYMENT_URL', null),
```

---

## Utilisation de base

### Méthode disponible

Le service [CustomMoneyFusionService.php:65](app/Services/CustomMoneyFusionService.php#L65) contient déjà la méthode `checkPaymentStatus()` :

```php
/**
 * Vérifier le statut d'un paiement
 *
 * @param string $tokenPay Le token du paiement à vérifier
 * @return array Résultat de la vérification
 * @throws MoneyFusionException
 */
public function checkPaymentStatus(string $tokenPay): array
{
    // Construit l'URL: https://www.pay.moneyfusion.net/paiementNotif/{token}
    $url = rtrim($this->checkPaymentUrl, '/') . '/' . $tokenPay;

    // Effectue la requête GET avec gestion SSL
    $response = Http::timeout($this->timeout)
        ->withOptions(['verify' => $this->verifySSL])
        ->get($url);

    // Met à jour en base de données si succès
    if ($response->successful() && isset($result['data'])) {
        $this->updatePaymentStatus($tokenPay, $result['data']);
    }

    return $result;
}
```

### Exemple simple

```php
use App\Services\CustomMoneyFusionService;

$moneyFusion = app(CustomMoneyFusionService::class);

try {
    $result = $moneyFusion->checkPaymentStatus('691bca13768d5ff857cf5958');

    if ($result['statut']) {
        echo "Statut du paiement: " . $result['data']['statut'];
    }
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
```

---

## Cas d'usage pratiques

### 1. Vérifier un paiement dans le callback

Mettre à jour [PaymentCallbackController.php](app/Http/Controllers/MoneyFusion/PaymentCallbackController.php) :

```php
<?php

namespace App\Http\Controllers\MoneyFusion;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use App\Services\CustomMoneyFusionService;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    protected $moneyFusion;

    public function __construct(CustomMoneyFusionService $moneyFusion)
    {
        $this->moneyFusion = $moneyFusion;
    }

    public function callback(Request $request)
    {
        $token = $request->get('token');

        if (!$token) {
            return redirect()->route('checkout.error')
                ->with('error', 'Token de paiement manquant');
        }

        $payment = MoneyFusionPayment::where('token_pay', $token)->first();

        if (!$payment) {
            return redirect()->route('checkout.error')
                ->with('error', 'Paiement introuvable');
        }

        // ✨ NOUVEAU: Vérifier le statut en temps réel avant de rediriger
        try {
            Log::info('Payment Callback: Checking status', ['token' => $token]);

            $statusCheck = $this->moneyFusion->checkPaymentStatus($token);

            if ($statusCheck['statut'] && isset($statusCheck['data']['statut'])) {
                $actualStatus = $statusCheck['data']['statut'];

                Log::info('Payment Callback: Status verified', [
                    'token' => $token,
                    'status' => $actualStatus
                ]);

                // Recharger le paiement depuis la base (mis à jour par checkPaymentStatus)
                $payment->refresh();
            }
        } catch (\Exception $e) {
            Log::warning('Payment Callback: Status check failed, using local data', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            // Continuer avec les données locales en cas d'erreur API
        }

        $order = Order::find($payment->order_id);

        if (!$order) {
            return redirect()->route('checkout.error')
                ->with('error', 'Commande introuvable');
        }

        // Redirection selon le statut
        if ($payment->statut === 'paid') {
            return redirect()->route('checkout.success', $order)
                ->with('success', 'Paiement confirmé avec succès !');
        } elseif ($payment->statut === 'pending') {
            return redirect()->route('checkout.pending', $order)
                ->with('info', 'Paiement en cours de traitement...');
        } elseif ($payment->statut === 'failed') {
            return redirect()->route('checkout.error')
                ->with('error', 'Le paiement a échoué. Veuillez réessayer.');
        }

        return redirect()->route('checkout.error')
            ->with('error', 'Statut de paiement inconnu');
    }
}
```

### 2. Vérifier le statut depuis la page de commande utilisateur

Créer une route AJAX pour vérifier le paiement en temps réel :

**Fichier** : `routes/web.php`

```php
// Check payment status (AJAX)
Route::get('/payment/check-status/{token}', [PaymentCallbackController::class, 'checkStatus'])
    ->name('payment.check-status');
```

**Contrôleur** : `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`

```php
/**
 * Vérifier le statut d'un paiement via AJAX
 */
public function checkStatus(Request $request, string $token)
{
    try {
        $payment = MoneyFusionPayment::where('token_pay', $token)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable'
            ], 404);
        }

        // Vérifier le statut en ligne
        $statusCheck = $this->moneyFusion->checkPaymentStatus($token);

        // Recharger depuis la base (mis à jour automatiquement)
        $payment->refresh();

        return response()->json([
            'success' => true,
            'status' => $payment->statut,
            'transaction_number' => $payment->numero_transaction,
            'payment_method' => $payment->moyen,
            'paid_at' => $payment->paid_at?->format('d/m/Y H:i'),
            'amount' => $payment->montant,
            'fees' => $payment->frais,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la vérification: ' . $e->getMessage()
        ], 500);
    }
}
```

**Vue** : `resources/views/account/orders/show.blade.php`

```blade
@if($order->payment_status === 'pending' && $order->moneyfusion_token)
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between">
        <div>
            <h4 class="font-semibold text-yellow-800">Paiement en attente</h4>
            <p class="text-sm text-yellow-600">Vérification du statut en cours...</p>
        </div>
        <button id="check-payment-btn"
                data-token="{{ $order->moneyfusion_token }}"
                class="btn btn-sm btn-primary">
            <i class="fas fa-sync-alt"></i> Vérifier maintenant
        </button>
    </div>
    <div id="payment-status" class="mt-3 hidden">
        <!-- Résultat de la vérification -->
    </div>
</div>

@push('scripts')
<script>
document.getElementById('check-payment-btn').addEventListener('click', function() {
    const btn = this;
    const token = btn.dataset.token;
    const statusDiv = document.getElementById('payment-status');

    // Désactiver le bouton
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification...';

    // Appeler l'API
    fetch(`/payment/check-status/${token}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.classList.remove('hidden');

                if (data.status === 'paid') {
                    statusDiv.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded p-3">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <strong>Paiement confirmé !</strong>
                            <p class="text-sm mt-1">Transaction: ${data.transaction_number}</p>
                            <p class="text-sm">Méthode: ${data.payment_method}</p>
                        </div>
                    `;

                    // Recharger la page après 2 secondes
                    setTimeout(() => location.reload(), 2000);
                } else if (data.status === 'failed') {
                    statusDiv.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded p-3">
                            <i class="fas fa-times-circle text-red-600"></i>
                            <strong>Paiement échoué</strong>
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = `
                        <div class="bg-blue-50 border border-blue-200 rounded p-3">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            <strong>Statut: ${data.status}</strong>
                        </div>
                    `;
                }
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erreur de connexion');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt"></i> Vérifier à nouveau';
        });
});

// Vérification automatique toutes les 30 secondes si paiement en attente
@if($order->payment_status === 'pending')
setInterval(function() {
    document.getElementById('check-payment-btn').click();
}, 30000);
@endif
</script>
@endpush
@endif
```

---

## Commande Artisan pour vérification automatique

Créer une commande pour vérifier tous les paiements en attente :

**Fichier** : `app/Console/Commands/CheckPendingPayments.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use App\Services\CustomMoneyFusionService;
use App\Models\Order;

class CheckPendingPayments extends Command
{
    protected $signature = 'moneyfusion:check-pending
                            {--age=30 : Vérifier les paiements de plus de X minutes}
                            {--limit=50 : Nombre maximum de paiements à vérifier}';

    protected $description = 'Vérifier le statut des paiements MoneyFusion en attente';

    protected $moneyFusion;

    public function __construct(CustomMoneyFusionService $moneyFusion)
    {
        parent::__construct();
        $this->moneyFusion = $moneyFusion;
    }

    public function handle()
    {
        $age = $this->option('age');
        $limit = $this->option('limit');

        $this->info("🔍 Vérification des paiements en attente (> {$age} minutes)...");

        // Récupérer les paiements en attente
        $pendingPayments = MoneyFusionPayment::where('statut', 'pending')
            ->where('created_at', '<=', now()->subMinutes($age))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('✅ Aucun paiement en attente à vérifier.');
            return 0;
        }

        $this->info("📋 {$pendingPayments->count()} paiements à vérifier...\n");

        $updated = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($pendingPayments->count());
        $progressBar->start();

        foreach ($pendingPayments as $payment) {
            try {
                // Vérifier le statut en ligne
                $result = $this->moneyFusion->checkPaymentStatus($payment->token_pay);

                if ($result['statut'] && isset($result['data']['statut'])) {
                    $newStatus = $result['data']['statut'];

                    if ($newStatus !== 'pending') {
                        // Statut changé
                        $this->newLine();
                        $this->line("  ✓ Paiement {$payment->token_pay}: {$payment->statut} → {$newStatus}");

                        // Mettre à jour la commande associée
                        if ($payment->order_id) {
                            $order = Order::find($payment->order_id);
                            if ($order && $newStatus === 'paid') {
                                $order->update([
                                    'payment_status' => 'paid',
                                    'status' => 'processing'
                                ]);
                                $this->line("    → Commande #{$order->order_number} mise à jour");
                            }
                        }

                        $updated++;
                    }
                }

                // Pause pour éviter de surcharger l'API
                usleep(200000); // 200ms

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  ✗ Erreur pour {$payment->token_pay}: {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Résumé
        $this->info("📊 Résumé:");
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['Mis à jour', $updated],
                ['Erreurs', $failed],
                ['Inchangés', $pendingPayments->count() - $updated - $failed],
            ]
        );

        return 0;
    }
}
```

### Enregistrement de la commande

**Fichier** : `app/Console/Kernel.php`

```php
protected $commands = [
    \App\Console\Commands\CheckPendingPayments::class,
];

protected function schedule(Schedule $schedule)
{
    // Vérifier les paiements en attente toutes les 10 minutes
    $schedule->command('moneyfusion:check-pending --age=10')
        ->everyTenMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
```

### Utilisation

```bash
# Vérifier les paiements en attente de plus de 30 minutes (par défaut)
php artisan moneyfusion:check-pending

# Vérifier les paiements de plus de 10 minutes
php artisan moneyfusion:check-pending --age=10

# Limiter à 20 paiements
php artisan moneyfusion:check-pending --limit=20

# Vérifier tous les paiements récents
php artisan moneyfusion:check-pending --age=5 --limit=100
```

---

## Interface admin pour vérification manuelle

Ajouter un bouton dans l'interface admin des commandes :

**Fichier** : `resources/views/admin/orders/show.blade.php`

```blade
@if($order->payment_method === 'moneyfusion' && $order->payment_status === 'pending')
<div class="card mb-4">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0">
            <i class="fas fa-exclamation-triangle"></i>
            Paiement MoneyFusion en attente
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p><strong>Token:</strong> {{ $order->moneyfusion_token }}</p>
                <p><strong>Créé le:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                <p class="mb-0">
                    <small class="text-muted">
                        Le paiement n'a pas encore été confirmé.
                        Cliquez sur "Vérifier" pour interroger MoneyFusion.
                    </small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <form action="{{ route('admin.orders.check-payment', $order) }}"
                      method="POST"
                      id="check-payment-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-sync-alt"></i>
                        Vérifier le paiement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
```

**Contrôleur** : `app/Http/Controllers/Admin/AdminOrderController.php`

```php
use App\Services\CustomMoneyFusionService;

/**
 * Vérifier manuellement le statut d'un paiement MoneyFusion
 */
public function checkPayment(Order $order, CustomMoneyFusionService $moneyFusion)
{
    if (!$order->moneyfusion_token) {
        return redirect()->back()
            ->with('error', 'Cette commande n\'a pas de token MoneyFusion');
    }

    try {
        Log::info('Admin: Checking payment status', [
            'order_id' => $order->id,
            'token' => $order->moneyfusion_token
        ]);

        // Vérifier le statut en ligne
        $result = $moneyFusion->checkPaymentStatus($order->moneyfusion_token);

        if ($result['statut'] && isset($result['data']['statut'])) {
            $paymentStatus = $result['data']['statut'];

            // Récupérer le paiement depuis la base
            $payment = MoneyFusionPayment::where('token_pay', $order->moneyfusion_token)->first();

            if ($payment) {
                // Mettre à jour la commande selon le statut
                if ($paymentStatus === 'paid') {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing'
                    ]);

                    return redirect()->back()
                        ->with('success', "✅ Paiement confirmé ! Transaction: {$payment->numero_transaction}");
                } elseif ($paymentStatus === 'failed') {
                    $order->update(['payment_status' => 'failed']);

                    return redirect()->back()
                        ->with('warning', '⚠️ Le paiement a échoué');
                } else {
                    return redirect()->back()
                        ->with('info', "ℹ️ Statut actuel: {$paymentStatus}");
                }
            }
        }

        return redirect()->back()
            ->with('warning', 'Aucune mise à jour de statut disponible');

    } catch (\Exception $e) {
        Log::error('Admin: Payment check failed', [
            'order_id' => $order->id,
            'error' => $e->getMessage()
        ]);

        return redirect()->back()
            ->with('error', 'Erreur lors de la vérification: ' . $e->getMessage());
    }
}
```

**Route** : `routes/web.php`

```php
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // ... autres routes admin ...

    Route::post('/orders/{order}/check-payment', [AdminOrderController::class, 'checkPayment'])
        ->name('orders.check-payment');
});
```

---

## Job pour vérification en arrière-plan

Créer un job pour vérifier un paiement spécifique de manière asynchrone :

**Fichier** : `app/Jobs/CheckMoneyFusionPaymentJob.php`

```php
<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CustomMoneyFusionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

class CheckMoneyFusionPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300]; // Réessayer après 1min, 2min, 5min

    protected string $tokenPay;

    public function __construct(string $tokenPay)
    {
        $this->tokenPay = $tokenPay;
    }

    public function handle(CustomMoneyFusionService $moneyFusion)
    {
        Log::info('Job: Checking payment status', ['token' => $this->tokenPay]);

        try {
            // Vérifier le statut
            $result = $moneyFusion->checkPaymentStatus($this->tokenPay);

            if ($result['statut'] && isset($result['data']['statut'])) {
                $payment = MoneyFusionPayment::where('token_pay', $this->tokenPay)->first();

                if ($payment && $payment->order_id) {
                    $order = Order::find($payment->order_id);

                    if ($order && $result['data']['statut'] === 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing'
                        ]);

                        Log::info('Job: Payment confirmed', [
                            'token' => $this->tokenPay,
                            'order_id' => $order->id
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Job: Payment check failed', [
                'token' => $this->tokenPay,
                'error' => $e->getMessage()
            ]);

            throw $e; // Permet le retry
        }
    }
}
```

### Utilisation du Job

```php
// Dispatch immédiatement
CheckMoneyFusionPaymentJob::dispatch($tokenPay);

// Dispatch avec délai (vérifier après 5 minutes)
CheckMoneyFusionPaymentJob::dispatch($tokenPay)->delay(now()->addMinutes(5));

// Dans le callback controller
public function callback(Request $request)
{
    $token = $request->get('token');

    // Dispatcher une vérification en arrière-plan après 2 minutes
    // pour confirmer le statut même si le webhook échoue
    CheckMoneyFusionPaymentJob::dispatch($token)
        ->delay(now()->addMinutes(2));

    // ... reste du code ...
}
```

---

## API endpoint pour vérification

Créer un endpoint API pour les applications externes :

**Route** : `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payments/{token}/check', [ApiPaymentController::class, 'checkStatus']);
});
```

**Contrôleur** : `app/Http/Controllers/Api/ApiPaymentController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomMoneyFusionService;
use Illuminate\Http\JsonResponse;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

class ApiPaymentController extends Controller
{
    protected $moneyFusion;

    public function __construct(CustomMoneyFusionService $moneyFusion)
    {
        $this->moneyFusion = $moneyFusion;
    }

    /**
     * Vérifier le statut d'un paiement
     *
     * @param string $token
     * @return JsonResponse
     */
    public function checkStatus(string $token): JsonResponse
    {
        try {
            $payment = MoneyFusionPayment::where('token_pay', $token)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Vérifier que l'utilisateur a accès à ce paiement
            if ($payment->order && $payment->order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Vérifier le statut en ligne
            $result = $this->moneyFusion->checkPaymentStatus($token);

            // Recharger depuis la base
            $payment->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $payment->token_pay,
                    'status' => $payment->statut,
                    'amount' => $payment->montant,
                    'transaction_number' => $payment->numero_transaction,
                    'payment_method' => $payment->moyen,
                    'fees' => $payment->frais,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'order_id' => $payment->order_id,
                    'created_at' => $payment->created_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

### Utilisation de l'API

```bash
# Avec authentification Bearer token
curl -X GET https://klab-consulting.com/api/payments/691bca13768d5ff857cf5958/check \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## Résumé des méthodes de vérification

| Méthode | Quand l'utiliser | Avantages | Inconvénients |
|---------|------------------|-----------|---------------|
| **Dans le callback** | Redirection utilisateur | Statut à jour avant affichage | Ralentit la redirection |
| **AJAX sur page commande** | Page "Mes commandes" | Feedback en temps réel | Requiert interaction utilisateur |
| **Commande Artisan** | Vérification batch/cron | Automatique, tous les paiements | Délai jusqu'à prochaine exécution |
| **Interface admin** | Gestion manuelle | Contrôle total | Manuel |
| **Job en arrière-plan** | Après callback/webhook | Asynchrone, pas de ralentissement | Complexité (queue) |
| **API endpoint** | Intégrations externes | Réutilisable | Requiert authentification |

---

## Configuration recommandée en production

### 1. Dans `.env`

```env
# URL de vérification MoneyFusion
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif

# Activer le logging
MONEYFUSION_LOGGING_ENABLED=true

# SSL toujours activé en production
MONEYFUSION_VERIFY_SSL=true
```

### 2. Dans `app/Console/Kernel.php`

```php
// Vérifier les paiements en attente toutes les 10 minutes
$schedule->command('moneyfusion:check-pending --age=10 --limit=100')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Vérifier les anciens paiements (> 24h) une fois par jour
$schedule->command('moneyfusion:check-pending --age=1440 --limit=500')
    ->daily()
    ->at('03:00');
```

### 3. Activer la queue

```bash
# Lancer le worker de queue
php artisan queue:work --tries=3 --timeout=90

# Ou avec supervisor en production (recommandé)
```

---

## Logs et debugging

### Logs de vérification

Les vérifications sont automatiquement loguées dans `storage/logs/laravel.log` :

```
[2025-11-19 10:30:15] production.INFO: MoneyFusion: Checking payment status
{"url":"https://www.pay.moneyfusion.net/paiementNotif/691bca13768d5ff857cf5958","token":"691bca13768d5ff857cf5958"}

[2025-11-19 10:30:16] production.INFO: MoneyFusion: Payment status updated
{"token":"691bca13768d5ff857cf5958","old_status":"pending","new_status":"paid"}
```

### Surveiller les logs

```bash
# Sur le serveur Hostinger
tail -f ~/domains/klab-consulting.com/laravel/storage/logs/laravel.log | grep -i "Checking payment"
```

---

**Document créé le** : 2025-11-19
**Version** : 1.0
**Complément de** : [MONEYFUSION-IMPLEMENTATION.md](MONEYFUSION-IMPLEMENTATION.md)
