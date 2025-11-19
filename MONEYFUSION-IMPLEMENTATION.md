# Documentation MoneyFusion - Implémentation E-commerce

## Table des matières

1. [Processus de paiement](#processus-de-paiement)
2. [Implémentation actuelle](#implémentation-actuelle)
3. [Architecture technique](#architecture-technique)
4. [Modifications recommandées pour le package](#modifications-recommandées-pour-le-package)
5. [Routes et endpoints](#routes-et-endpoints)
6. [Résolution de problèmes](#résolution-de-problèmes)

---

## Processus de paiement

### Vue d'ensemble

Lorsqu'un utilisateur passe une commande, le processus de paiement MoneyFusion se déroule en plusieurs étapes :

```
┌─────────────────┐
│  1. Création    │
│  de commande    │
└────────┬────────┘
         │
         v
┌─────────────────┐
│  2. Initialisa- │
│  tion paiement  │
└────────┬────────┘
         │
         v
┌─────────────────┐
│  3. Redirection │
│  MoneyFusion    │
└────────┬────────┘
         │
         v
    ┌────┴────┐
    │         │
    v         v
┌────────┐  ┌──────────┐
│Callback│  │ Webhook  │
│(User)  │  │ (Server) │
└───┬────┘  └────┬─────┘
    │            │
    v            v
┌────────────────────┐
│  5. Confirmation   │
│  & mise à jour     │
└────────────────────┘
```

### Étapes détaillées

#### 1. Création de la commande

**Fichier** : `app/Http/Controllers/CheckoutController.php`

```php
public function placeOrder(Request $request)
{
    // Validation des données
    $validated = $request->validate([...]);

    // Création de la commande
    $order = Order::create([
        'user_id' => auth()->id() ?? null,
        'order_number' => 'ORD-' . strtoupper(uniqid()),
        'total' => $cartTotal,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'moneyfusion',
    ]);

    // Enregistrement des items de commande
    foreach ($cart as $item) {
        OrderItem::create([...]);
    }
}
```

**Statuts initiaux** :
- `order.status` = `pending`
- `order.payment_status` = `pending`

#### 2. Initialisation du paiement MoneyFusion

**Fichier** : `app/Http/Controllers/CheckoutController.php`

```php
// Configuration du paiement
$payload = [
    'amount' => $order->total,
    'description' => "Commande #" . $order->order_number,
    'return_url' => route('payment.callback'),        // URL de retour utilisateur
    'webhook_url' => url('/api/moneyfusion/webhook'), // URL de notification serveur
];

// Initialisation via le service MoneyFusion
$response = $moneyFusionService->initializePayment($payload);

// Enregistrement du token de paiement
MoneyFusionPayment::create([
    'order_id' => $order->id,
    'token_pay' => $response['token'],
    'amount' => $order->total,
    'statut' => 'pending',
]);

// Redirection vers la page de paiement MoneyFusion
return redirect($response['payment_url']);
```

**Points clés** :
- Deux URLs sont configurées : `return_url` (callback) et `webhook_url`
- Un token unique est généré par MoneyFusion
- L'utilisateur est redirigé vers la page de paiement sécurisée

#### 3. Page de paiement MoneyFusion

L'utilisateur se trouve maintenant sur la page MoneyFusion où il peut :
- Choisir son mode de paiement (Mobile Money, carte bancaire, etc.)
- Entrer ses informations de paiement
- Confirmer la transaction

**Pendant ce temps** :
- La commande reste en statut `pending`
- Le système attend la notification de MoneyFusion

#### 4. Notifications parallèles (Callback + Webhook)

Une fois le paiement traité, MoneyFusion envoie **deux notifications simultanées** :

##### 4.a. Callback (Redirection utilisateur)

**Route** : `GET /payment/callback?token={token}`
**Contrôleur** : `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`

```php
public function callback(Request $request)
{
    $token = $request->get('token');
    $payment = MoneyFusionPayment::where('token_pay', $token)->first();

    if (!$payment) {
        return redirect()->route('checkout.error')
            ->with('error', 'Paiement introuvable');
    }

    $order = Order::find($payment->order_id);

    // Redirection selon le statut
    if ($payment->statut === 'paid') {
        return redirect()->route('checkout.success', $order)
            ->with('success', 'Paiement réussi !');
    }

    return redirect()->route('checkout.error')
        ->with('error', 'Paiement échoué');
}
```

**Caractéristiques** :
- Dépend de la présence de l'utilisateur
- Affiche une page de confirmation immédiate
- Si l'utilisateur ferme le navigateur, cette callback n'est pas appelée

##### 4.b. Webhook (Notification serveur)

**Route** : `POST /api/moneyfusion/webhook`
**Contrôleur** : `app/Http/Controllers/MoneyFusion/WebhookController.php`

```php
public function handle(Request $request): JsonResponse
{
    Log::info('MoneyFusion Webhook received', ['data' => $request->all()]);

    $data = $request->all();
    $token = $data['token'];

    // Récupération du paiement
    $payment = MoneyFusionPayment::where('token_pay', $token)->first();

    // Mise à jour du paiement
    $payment->update([
        'statut' => $data['statut'] ?? 'pending',
        'numero_transaction' => $data['numeroTransaction'] ?? null,
        'moyen' => $data['moyen'] ?? null,
        'frais' => $data['frais'] ?? 0,
        'paid_at' => ($data['statut'] === 'paid') ? now() : null,
    ]);

    // Mise à jour de la commande
    if ($payment->order_id) {
        $order = Order::find($payment->order_id);

        if ($data['statut'] === 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
            ]);
        } elseif ($data['statut'] === 'failed') {
            $order->update(['payment_status' => 'failed']);
        } elseif ($data['statut'] === 'cancelled') {
            $order->update([
                'payment_status' => 'cancelled',
                'status' => 'cancelled'
            ]);
        }
    }

    return response()->json(['success' => true]);
}
```

**Caractéristiques** :
- Fonctionne indépendamment de l'utilisateur
- Garantit la mise à jour du statut même si le navigateur est fermé
- **Plus fiable** pour la synchronisation des paiements
- Protection CSRF désactivée (appel externe)

#### 5. Confirmation et mise à jour

**Statuts finaux selon le résultat** :

| Résultat | payment_status | order.status | Action |
|----------|---------------|--------------|--------|
| Succès | `paid` | `processing` | Commande prête à être traitée |
| Échec | `failed` | `pending` | Utilisateur peut réessayer |
| Annulé | `cancelled` | `cancelled` | Commande annulée |

---

## Implémentation actuelle

### Options utilisées

Notre implémentation utilise **LES DEUX options simultanément** :

#### ✓ Option 3.a : Callback (Redirection)

- **Route** : `GET /payment/callback`
- **Fichier** : `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`
- **Usage** : Redirection de l'utilisateur après paiement
- **Avantage** : Feedback immédiat pour l'utilisateur

#### ✓ Option 3.b : Webhook (POST)

- **Route** : `POST /api/moneyfusion/webhook`
- **Fichier** : `app/Http/Controllers/MoneyFusion/WebhookController.php`
- **Usage** : Notification serveur-à-serveur
- **Avantage** : Fiabilité garantie même sans présence utilisateur

### Pourquoi les deux ?

Cette approche **double** offre :

1. **Expérience utilisateur optimale** (Callback)
   - Page de confirmation immédiate
   - Affichage du récapitulatif de commande
   - Feedback visuel instantané

2. **Fiabilité maximale** (Webhook)
   - Synchronisation garantie
   - Fonctionne même si l'utilisateur ferme le navigateur
   - Traitement en arrière-plan
   - Idéal pour la réconciliation des paiements

### Configuration dans le code

**Fichier** : `app/Http/Controllers/CheckoutController.php`

```php
$payload = [
    'amount' => $order->total,
    'description' => "Commande #" . $order->order_number,
    'return_url' => route('payment.callback'),  // ← Callback (redirection)
    'webhook_url' => url('/api/moneyfusion/webhook')  // ← Webhook (POST)
];

$response = $moneyFusionService->initializePayment($payload);
```

---

## Architecture technique

### Structure des fichiers

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── CheckoutController.php           # Initialisation paiement
│   │   └── MoneyFusion/
│   │       ├── PaymentCallbackController.php # Callback utilisateur
│   │       └── WebhookController.php         # Webhook serveur
│   └── Middleware/
│       └── VerifyCsrfToken.php              # Exclusion webhook
├── Models/
│   ├── Order.php
│   └── MoneyFusionPayment.php (package)
└── Services/
    └── MoneyFusionService.php               # Service personnalisé

routes/
└── web.php                                   # Définition des routes

config/
└── moneyfusion.php                          # Configuration package
```

### Base de données

#### Table `orders`

```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULL,
    order_number VARCHAR(255),
    total DECIMAL(10,2),
    status ENUM('pending','processing','completed','cancelled'),
    payment_status ENUM('pending','paid','failed','refunded','cancelled'),
    payment_method VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Table `money_fusion_payments`

```sql
CREATE TABLE money_fusion_payments (
    id BIGINT PRIMARY KEY,
    order_id BIGINT NULL,
    token_pay VARCHAR(255),
    amount DECIMAL(10,2),
    statut VARCHAR(50),
    numero_transaction VARCHAR(255) NULL,
    moyen VARCHAR(50) NULL,
    frais DECIMAL(10,2) DEFAULT 0,
    paid_at TIMESTAMP NULL,
    raw_response JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Service MoneyFusion personnalisé

**Fichier** : `app/Services/MoneyFusionService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoneyFusionService
{
    private $apiUrl;
    private $apiKey;
    private $verifySSL;

    public function __construct()
    {
        $this->apiUrl = config('moneyfusion.api_url');
        $this->apiKey = config('moneyfusion.api_key');
        $this->verifySSL = config('moneyfusion.verify_ssl', true);
    }

    public function initializePayment(array $payload)
    {
        Log::info('MoneyFusion: Creating payment', [
            'amount' => $payload['amount'],
            'webhook_url' => $payload['webhook_url'] ?? null
        ]);

        $response = Http::withOptions(['verify' => $this->verifySSL])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->post($this->apiUrl . '/initialize', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('MoneyFusion payment initialization failed: ' . $response->body());
    }
}
```

**Avantages** :
- Configuration SSL personnalisable
- Logging détaillé
- Gestion d'erreurs robuste

---

## Modifications recommandées pour le package

### Problème actuel

Le package `simonet85/laravel-moneyfusion` enregistre automatiquement une route webhook qui entre en conflit avec notre route personnalisée :

```
POST /moneyfusion/webhook → name: moneyfusion.webhook (package)
POST /api/moneyfusion/webhook → (notre route personnalisée)
```

**Erreur** :
```
Unable to prepare route [api/moneyfusion/webhook] for serialization.
Another route has already been assigned name [moneyfusion.webhook].
```

### Solution 1 : Routes optionnelles (Recommandée)

Modifier le package pour rendre l'enregistrement automatique des routes optionnel.

#### Fichier : `config/moneyfusion.php`

```php
<?php

return [
    'api_url' => env('MONEYFUSION_API_URL', 'https://api.moneyfusion.net'),
    'api_key' => env('MONEYFUSION_API_KEY'),
    'verify_ssl' => env('MONEYFUSION_VERIFY_SSL', true),

    // Nouvelle option
    'register_routes' => env('MONEYFUSION_REGISTER_ROUTES', true),
];
```

#### Fichier : `src/MoneyFusionServiceProvider.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class MoneyFusionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Configuration
        $this->publishes([
            __DIR__.'/../config/moneyfusion.php' => config_path('moneyfusion.php'),
        ], 'config');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Routes optionnelles
        if (config('moneyfusion.register_routes', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => 'api',
            'middleware' => ['api'],
        ], function () {
            Route::post('/moneyfusion/webhook', [WebhookController::class, 'handle'])
                ->name('moneyfusion.webhook')
                ->withoutMiddleware(['csrf']);
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/moneyfusion.php', 'moneyfusion'
        );
    }
}
```

#### Utilisation

Dans le projet Laravel, définir dans `.env` :

```env
# Désactiver les routes automatiques du package
MONEYFUSION_REGISTER_ROUTES=false
```

Puis définir nos propres routes dans `routes/web.php` :

```php
Route::post('/api/moneyfusion/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### Solution 2 : Préfixe configurable

Permettre de personnaliser le préfixe de route du package.

#### Fichier : `config/moneyfusion.php`

```php
<?php

return [
    'api_url' => env('MONEYFUSION_API_URL', 'https://api.moneyfusion.net'),
    'api_key' => env('MONEYFUSION_API_KEY'),
    'verify_ssl' => env('MONEYFUSION_VERIFY_SSL', true),

    // Préfixe personnalisable
    'webhook_path' => env('MONEYFUSION_WEBHOOK_PATH', 'api/moneyfusion/webhook'),
];
```

#### Fichier : `src/MoneyFusionServiceProvider.php`

```php
protected function registerRoutes()
{
    $webhookPath = config('moneyfusion.webhook_path', 'api/moneyfusion/webhook');

    Route::post($webhookPath, [WebhookController::class, 'handle'])
        ->name('moneyfusion.webhook')
        ->withoutMiddleware(['csrf']);
}
```

#### Utilisation

```env
# Dans .env
MONEYFUSION_WEBHOOK_PATH=custom/webhook/path
```

### Solution 3 : Helper pour l'URL de webhook

Fournir une fonction helper pour générer l'URL de webhook dynamiquement.

#### Fichier : `src/helpers.php`

```php
<?php

if (!function_exists('moneyfusion_webhook_url')) {
    /**
     * Generate the MoneyFusion webhook URL
     *
     * @return string
     */
    function moneyfusion_webhook_url(): string
    {
        $path = config('moneyfusion.webhook_path', 'api/moneyfusion/webhook');
        return url($path);
    }
}
```

#### Fichier : `composer.json`

```json
{
    "autoload": {
        "files": [
            "src/helpers.php"
        ]
    }
}
```

#### Utilisation dans CheckoutController

```php
$payload = [
    'amount' => $order->total,
    'description' => "Commande #" . $order->order_number,
    'return_url' => route('payment.callback'),
    'webhook_url' => moneyfusion_webhook_url(), // ← Helper function
];
```

### Comparaison des solutions

| Solution | Avantages | Inconvénients | Recommandation |
|----------|-----------|---------------|----------------|
| **1. Routes optionnelles** | Flexibilité maximale, pas de conflit | Requiert modification du package | ⭐⭐⭐⭐⭐ |
| **2. Préfixe configurable** | Configuration simple | Peut causer des conflits si mal configuré | ⭐⭐⭐⭐ |
| **3. Helper URL** | Facile à utiliser | Ne résout pas le problème de conflit de routes | ⭐⭐⭐ |

### Implémentation recommandée

**Solution 1 (Routes optionnelles)** est la meilleure approche car elle :
- Offre une flexibilité maximale aux développeurs
- Suit les conventions Laravel modernes
- Évite complètement les conflits de routes
- Permet aux projets de définir leurs propres routes personnalisées

### Pull Request suggéré

Pour soumettre ces modifications au package :

1. **Fork** le repository : `https://github.com/simonet85/laravel-moneyfusion`
2. **Créer une branche** : `git checkout -b feature/optional-routes`
3. **Implémenter** la Solution 1
4. **Mettre à jour** le README avec la documentation
5. **Soumettre** un Pull Request avec description détaillée
6. **Bump version** : `1.0.x → 1.1.0` (ajout de fonctionnalité)

---

## Routes et endpoints

### Routes définies dans l'application

**Fichier** : `routes/web.php`

```php
// MoneyFusion Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/callback', [PaymentCallbackController::class, 'callback'])
        ->name('callback');
});

// MoneyFusion Webhook (POST endpoint - CSRF excluded in bootstrap/app.php)
// Note: Package also registers a webhook route, but we use our custom controller
Route::post('/api/moneyfusion/webhook', [WebhookController::class, 'handle']);
```

### Liste complète des routes

```bash
php artisan route:list --name=payment
```

| Méthode | URI | Nom | Contrôleur |
|---------|-----|-----|------------|
| GET | /payment/callback | payment.callback | PaymentCallbackController@callback |
| POST | /api/moneyfusion/webhook | - | WebhookController@handle |

### Protection CSRF (Laravel 12)

Le webhook **doit** être exclu de la protection CSRF car il est appelé par un serveur externe (MoneyFusion).

**⚠️ Important Laravel 12** : La configuration CSRF se fait maintenant dans `bootstrap/app.php` et non plus dans un middleware dédié.

**Fichier** : `bootstrap/app.php`

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'force-https' => \App\Http\Middleware\ForceHttps::class,
        ]);

        // Exclude MoneyFusion webhook from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'api/moneyfusion/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**Points clés** :
- Utiliser `$middleware->validateCsrfTokens(except: [...])` pour Laravel 12
- Le chemin est relatif à la racine du site : `api/moneyfusion/webhook`
- Pas besoin de `/` au début du chemin

---

## Résolution de problèmes

### Webhook non appelé

**Symptômes** :
- Paiements marqués comme réussis sur MoneyFusion
- Commandes restent en statut `pending` dans la base de données
- Aucun log de webhook dans `storage/logs/laravel.log`

**Solutions** :

1. **Vérifier que la route existe** :
   ```bash
   php artisan route:list | grep webhook
   ```

2. **Vérifier les logs** :
   ```bash
   tail -f storage/logs/laravel.log | grep -i webhook
   ```

3. **Tester le webhook manuellement** :
   ```bash
   curl -X POST https://klab-consulting.com/api/moneyfusion/webhook \
     -H "Content-Type: application/json" \
     -d '{
       "token": "test_token_123",
       "statut": "paid",
       "numeroTransaction": "TRX123456",
       "moyen": "mobile_money",
       "frais": 50
     }'
   ```

4. **Vérifier le cache des routes** :
   ```bash
   php artisan route:cache
   ```

5. **Vérifier la protection CSRF** :
   - S'assurer que `api/moneyfusion/webhook` est dans la liste `except` de `validateCsrfTokens()` dans `bootstrap/app.php`

### Erreur "Page Expired" (419)

**Symptômes** :
```bash
curl -X POST https://klab-consulting.com/api/moneyfusion/webhook \
  -H "Content-Type: application/json" \
  -d '{"token":"test","statut":"paid"}'

# Retourne: HTTP 419 + Page HTML "Page Expired"
```

**Cause** : Protection CSRF active sur le webhook

**Solution (Laravel 12)** :

Ajouter l'exception dans `bootstrap/app.php` :

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([...]);

    // Exclude MoneyFusion webhook from CSRF protection
    $middleware->validateCsrfTokens(except: [
        'api/moneyfusion/webhook',
    ]);
})
```

Puis vider le cache :
```bash
php artisan config:cache
php artisan route:cache
```

### Conflit de noms de routes

**Erreur** :
```
Unable to prepare route [api/moneyfusion/webhook] for serialization.
Another route has already been assigned name [moneyfusion.webhook].
```

**Solution** : Retirer le nom de notre route personnalisée :
```php
// Avant (conflit)
Route::post('/api/moneyfusion/webhook', [WebhookController::class, 'handle'])
    ->name('moneyfusion.webhook'); // ← Conflit avec le package

// Après (OK)
Route::post('/api/moneyfusion/webhook', [WebhookController::class, 'handle']);
```

### Erreur "Missing token" dans les webhooks

**Symptômes** :
```
[2025-11-19 13:48:42] production.INFO: MoneyFusion Webhook received
[2025-11-19 13:48:42] production.WARNING: MoneyFusion Webhook: Missing token
```

**Cause** : MoneyFusion envoie le champ `tokenPay` au lieu de `token`

**Données réelles reçues** :
```json
{
  "event": "payin.session.completed",
  "tokenPay": "691dca7d622fa841bbe5a6bc",  ← Le champ s'appelle "tokenPay"
  "statut": "paid",
  "numeroTransaction": "+2250767647896",
  "Montant": 194,
  "frais": 6,
  "moyen": "wave"
}
```

**Solution** :

Modifier `WebhookController.php` pour supporter les deux noms de champs :

```php
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

        // ... reste du code
    }
}
```

### Paiements non synchronisés

**Vérifications** :

1. **Vérifier la table `money_fusion_payments`** :
   ```sql
   SELECT id, order_id, token_pay, statut, paid_at
   FROM money_fusion_payments
   WHERE statut = 'paid'
   ORDER BY created_at DESC
   LIMIT 10;
   ```

2. **Vérifier les commandes correspondantes** :
   ```sql
   SELECT o.id, o.order_number, o.payment_status, o.status, mfp.statut
   FROM orders o
   JOIN money_fusion_payments mfp ON o.id = mfp.order_id
   WHERE o.payment_status != mfp.statut;
   ```

3. **Resynchroniser manuellement si nécessaire** :
   ```php
   // Dans tinker : php artisan tinker
   $payment = \Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment::find(123);
   $order = \App\Models\Order::find($payment->order_id);

   if ($payment->statut === 'paid') {
       $order->update([
           'payment_status' => 'paid',
           'status' => 'processing'
       ]);
   }
   ```

### Logs utiles

**Activer le logging détaillé** :

```php
// Dans WebhookController.php
Log::info('MoneyFusion Webhook received', [
    'data' => $request->all(),
    'headers' => $request->headers->all(),
    'ip' => $request->ip()
]);
```

**Surveiller les logs en temps réel** :
```bash
# Sur le serveur Hostinger
tail -f ~/domains/klab-consulting.com/laravel/storage/logs/laravel.log | grep -i webhook
```

**Exemple de logs d'un paiement réussi** :

```
[2025-11-19 13:59:51] production.INFO: MoneyFusion: Creating payment
{"payload":{"totalPrice":"200","webhook_url":"https://klab-consulting.com/api/moneyfusion/webhook"}}

[2025-11-19 13:59:52] production.INFO: MoneyFusion Webhook received
{"data":{"event":"payin.session.pending","tokenPay":"691dcd58622fa841bbe5bdf6","statut":"pending"}}

[2025-11-19 13:59:52] production.WARNING: MoneyFusion Webhook: Payment not found
{"token":"691dcd58622fa841bbe5bdf6"}
# Note: Normal si le paiement n'est pas encore enregistré en base

[2025-11-19 14:00:47] production.INFO: MoneyFusion Webhook received
{"data":{"event":"payin.session.completed","tokenPay":"691dcd58622fa841bbe5bdf6","statut":"paid","numeroTransaction":"+2250767647896","Montant":194,"frais":6,"moyen":"wave"}}

[2025-11-19 14:00:47] production.INFO: MoneyFusion Webhook: Payment updated
{"token":"691dcd58622fa841bbe5bdf6","status":"paid","order_id":12}
```

---

## Résumé des corrections apportées

### ✅ Correction 1 : Protection CSRF (Commit ac7b4c3)

**Problème** : HTTP 419 "Page Expired"
**Solution** : Exclusion CSRF dans `bootstrap/app.php` (Laravel 12)

```php
$middleware->validateCsrfTokens(except: [
    'api/moneyfusion/webhook',
]);
```

### ✅ Correction 2 : Nom du champ token (Commit 9211087)

**Problème** : MoneyFusion envoie `tokenPay` au lieu de `token`
**Solution** : Support des deux noms avec null coalescing operator

```php
$token = $data['token'] ?? $data['tokenPay'] ?? null;
```

### ✅ Résultat

Webhook 100% fonctionnel :
- CSRF correctement désactivé
- Token extrait peu importe le nom du champ
- Mises à jour automatiques des commandes
- Logging complet pour le debugging

---

## Ressources

### Documentation officielle

- **Laravel** : https://laravel.com/docs
- **MoneyFusion API** : https://docs.moneyfusion.net
- **Package Laravel MoneyFusion** : https://github.com/simonet85/laravel-moneyfusion

### Fichiers importants

| Fichier | Description |
|---------|-------------|
| `app/Http/Controllers/CheckoutController.php` | Initialisation paiement |
| `app/Http/Controllers/MoneyFusion/WebhookController.php` | Traitement webhook |
| `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php` | Redirection utilisateur |
| `app/Services/MoneyFusionService.php` | Service personnalisé |
| `config/moneyfusion.php` | Configuration package |
| `routes/web.php` | Définition des routes |

### Variables d'environnement

```env
# MoneyFusion Configuration
MONEYFUSION_API_URL=https://api.moneyfusion.net
MONEYFUSION_API_KEY=your_api_key_here
MONEYFUSION_VERIFY_SSL=true

# Optional (if package is modified)
MONEYFUSION_REGISTER_ROUTES=false
MONEYFUSION_WEBHOOK_PATH=api/moneyfusion/webhook
```

---

**Document créé le** : 2025-11-19
**Version** : 1.0
**Application** : Glowing Cosmetics E-commerce
**Package** : simonet85/laravel-moneyfusion v1.0.x
