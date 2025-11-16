# Améliorations Recommandées pour le Package MoneyFusion

Ce document détaille les ajustements nécessaires pour améliorer le package `simonet85/laravel-moneyfusion` basés sur notre expérience d'intégration.

## Table des Matières

1. [Compatibilité Laravel 12](#1-compatibilité-laravel-12)
2. [Support SSL Verification Configurable](#2-support-ssl-verification-configurable)
3. [URL de Vérification de Paiement Flexible](#3-url-de-vérification-de-paiement-flexible)
4. [Nom de Table Cohérent](#4-nom-de-table-cohérent)
5. [Fallback pour Vérification de Statut](#5-fallback-pour-vérification-de-statut)
6. [Documentation de l'API Endpoint](#6-documentation-de-lapi-endpoint)
7. [Tests Automatisés](#7-tests-automatisés)
8. [Support de Composer 2.x](#8-support-de-composer-2x)
9. [Événements pour Webhooks](#9-événements-pour-webhooks)
10. [Gestion d'Erreurs Améliorée](#10-gestion-derreurs-améliorée)

---

## 1. Compatibilité Laravel 12 ⚠️ CRITIQUE

### Problème
Le package utilise une classe `Controller` qui n'existe pas dans Laravel 12, causant l'erreur:
```
Class "Simonet85\LaravelMoneyFusion\Http\Controllers\Controller" not found
```

### Solution

**Fichier**: `src/Http/Controllers/PaymentController.php`
**Fichier**: `src/Http/Controllers/WebhookController.php`

```php
namespace Simonet85\LaravelMoneyFusion\Http\Controllers;

// ❌ Avant
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // ...
}

// ✅ Après
use Illuminate\Http\Request;
use Illuminate\Routing\Controller; // Ajouter cette ligne

class PaymentController extends Controller
{
    // ...
}
```

### Impact
- **Sévérité**: Critique
- **Affecté**: Laravel 12+
- **Workaround actuel**: `MoneyFusionCompatibilityServiceProvider` avec class_alias

---

## 2. Support SSL Verification Configurable 🔒

### Problème
Impossible de désactiver la vérification SSL pour le développement local, causant:
```
cURL error 60: SSL certificate problem
```

### Solution

**Fichier**: `config/moneyfusion.php`

```php
return [
    // ... autres configurations

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Activer/désactiver la vérification SSL pour les requêtes HTTP.
    | IMPORTANT: Mettre à false uniquement en développement local.
    | TOUJOURS à true en production!
    |
    */
    'verify_ssl' => env('MONEYFUSION_VERIFY_SSL', true),
];
```

**Fichier**: `src/MoneyFusionService.php`

```php
class MoneyFusionService
{
    protected string $apiUrl;
    protected string $appKey;
    protected int $timeout;
    protected bool $verifySSL; // Ajouter cette propriété

    public function __construct()
    {
        $this->apiUrl = config('moneyfusion.api_url');
        $this->appKey = config('moneyfusion.app_key');
        $this->timeout = config('moneyfusion.timeout', 30);
        $this->verifySSL = config('moneyfusion.verify_ssl', true); // Ajouter

        if (empty($this->apiUrl) || empty($this->appKey)) {
            throw new MoneyFusionException('MoneyFusion configuration is missing.');
        }
    }

    public function createPayment(array $data): array
    {
        try {
            $payload = $this->preparePayload($data);

            Log::info('MoneyFusion: Creating payment', ['payload' => $payload]);

            // ✅ Ajouter withOptions pour gérer SSL
            $response = Http::timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySSL,
                ])
                ->post($this->apiUrl, $payload);

            // ... reste du code
        } catch (\Exception $e) {
            // ...
        }
    }

    public function checkPaymentStatus(string $tokenPay): array
    {
        try {
            $url = str_replace('/create-payment', "/check-payment/{$tokenPay}", $this->apiUrl);

            // ✅ Ajouter withOptions ici aussi
            $response = Http::timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySSL,
                ])
                ->get($url);

            // ... reste du code
        } catch (\Exception $e) {
            // ...
        }
    }
}
```

**Configuration utilisateur** (`.env`):
```env
# Développement local
MONEYFUSION_VERIFY_SSL=false

# Production
MONEYFUSION_VERIFY_SSL=true
```

### Impact
- **Sévérité**: Haute
- **Affecté**: Environnements de développement local (Laragon, XAMPP, WAMP)
- **Workaround actuel**: `CustomMoneyFusionService`

---

## 3. URL de Vérification de Paiement Flexible 🔄

### Problème
L'endpoint de vérification est construit automatiquement (`str_replace('/create-payment', '/check-payment'...)`) mais ne correspond pas à la nouvelle API MoneyFusion.

**API actuelle**: `https://www.pay.moneyfusion.net/paiementNotif/{token}`
**Code package**: Essaie de construire depuis l'URL de création

### Solution

**Fichier**: `config/moneyfusion.php`

```php
return [
    'api_url' => env('MONEYFUSION_API_URL', 'https://api.moneyfusion.net/api/create-payment'),

    /*
    |--------------------------------------------------------------------------
    | MoneyFusion Check Payment URL
    |--------------------------------------------------------------------------
    |
    | URL pour vérifier le statut d'un paiement.
    | Le token sera ajouté à la fin de cette URL.
    |
    | Exemple: https://www.pay.moneyfusion.net/paiementNotif
    | Résultat: https://www.pay.moneyfusion.net/paiementNotif/{token}
    |
    | Si non spécifié, l'URL sera construite automatiquement depuis api_url.
    |
    */
    'check_payment_url' => env('MONEYFUSION_CHECK_PAYMENT_URL', null),
];
```

**Fichier**: `src/MoneyFusionService.php`

```php
protected ?string $checkPaymentUrl;

public function __construct()
{
    $this->apiUrl = config('moneyfusion.api_url');
    $this->appKey = config('moneyfusion.app_key');
    $this->timeout = config('moneyfusion.timeout', 30);
    $this->checkPaymentUrl = config('moneyfusion.check_payment_url'); // Ajouter
}

public function checkPaymentStatus(string $tokenPay): array
{
    try {
        // ✅ Utiliser l'URL configurée si disponible
        if ($this->checkPaymentUrl) {
            $url = rtrim($this->checkPaymentUrl, '/') . '/' . $tokenPay;
        } else {
            // Fallback à l'ancien comportement
            $url = str_replace('/create-payment', "/check-payment/{$tokenPay}", $this->apiUrl);
        }

        Log::info('MoneyFusion: Checking payment status', [
            'url' => $url,
            'token' => $tokenPay
        ]);

        $response = Http::timeout($this->timeout)->get($url);

        // ... reste du code
    } catch (\Exception $e) {
        // ...
    }
}
```

**Configuration utilisateur** (`.env`):
```env
# Nouvelle API
MONEYFUSION_API_URL=https://www.pay.moneyfusion.net/AppName/ApiKey/pay/
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif

# Ancienne API (fonctionnera toujours)
MONEYFUSION_API_URL=https://api.moneyfusion.net/api/create-payment
# MONEYFUSION_CHECK_PAYMENT_URL non spécifié = construction automatique
```

### Impact
- **Sévérité**: Moyenne
- **Affecté**: Utilisateurs utilisant la nouvelle API MoneyFusion
- **Workaround actuel**: Configuration dans `.env` + `CustomMoneyFusionService`

---

## 4. Nom de Table Cohérent 📊

### Problème
Incohérence entre le nom de table généré par le modèle et celui créé par la migration:
- **Modèle Laravel**: `MoneyFusionPayment` → `money_fusion_payments` (convention snake_case)
- **Migration**: Crée `moneyfusion_payments`

### Solution

**Option A: Forcer le nom dans le modèle** (Recommandé)

**Fichier**: `src/Models/MoneyFusionPayment.php`

```php
class MoneyFusionPayment extends Model
{
    use HasFactory;

    // ✅ Forcer le nom de table explicitement
    protected $table = 'moneyfusion_payments';

    protected $fillable = [
        // ...
    ];
}
```

**Option B: Renommer la migration**

**Fichier**: `database/migrations/create_moneyfusion_payments_table.php`

```php
public function up()
{
    // ❌ Avant
    Schema::create('moneyfusion_payments', function (Blueprint $table) {

    // ✅ Après
    Schema::create('money_fusion_payments', function (Blueprint $table) {
        // ...
    });
}

public function down()
{
    // ❌ Avant
    Schema::dropIfExists('moneyfusion_payments');

    // ✅ Après
    Schema::dropIfExists('money_fusion_payments');
}
```

**Recommandation**: Option A (forcer dans le modèle) car plus simple et évite de casser les installations existantes.

### Impact
- **Sévérité**: Moyenne
- **Affecté**: Nouvelles installations
- **Workaround actuel**: Renommer manuellement la table ou ajouter `protected $table`

---

## 5. Fallback pour Vérification de Statut 🛡️

### Problème
Si l'API de vérification échoue ou n'est pas disponible, impossible de récupérer le statut du paiement, même depuis la base de données locale.

### Solution

**Fichier**: `src/MoneyFusionService.php`

```php
public function checkPaymentStatus(string $tokenPay): array
{
    try {
        // Construire l'URL
        if ($this->checkPaymentUrl) {
            $url = rtrim($this->checkPaymentUrl, '/') . '/' . $tokenPay;
        } else {
            $url = str_replace('/create-payment', "/check-payment/{$tokenPay}", $this->apiUrl);
        }

        Log::info('MoneyFusion: Checking payment status', [
            'url' => $url,
            'token' => $tokenPay
        ]);

        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySSL])
            ->get($url);

        // ✅ Ajouter fallback si l'API échoue
        if (!$response->successful()) {
            Log::warning('MoneyFusion: Check payment API returned error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Tenter de récupérer depuis la base de données locale
            $payment = $this->getPaymentByToken($tokenPay);

            if ($payment) {
                Log::info('MoneyFusion: Using local database fallback', [
                    'token' => $tokenPay,
                    'status' => $payment->statut
                ]);

                return [
                    'statut' => true,
                    'data' => [
                        'statut' => $payment->statut,
                        'montant' => $payment->montant,
                        'token' => $payment->token_pay,
                        'numeroTransaction' => $payment->numero_transaction,
                        'moyen' => $payment->moyen,
                        'frais' => $payment->frais,
                        'source' => 'local_database',
                        'message' => 'API vérification indisponible. Données de la base locale.'
                    ]
                ];
            }

            // Si pas de données locales non plus, lever une exception
            throw new MoneyFusionException('API error: ' . $response->body());
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

        // ✅ Dernier recours: essayer la base de données
        $payment = $this->getPaymentByToken($tokenPay);

        if ($payment) {
            return [
                'statut' => true,
                'data' => [
                    'statut' => $payment->statut,
                    'montant' => $payment->montant,
                    'token' => $payment->token_pay,
                    'source' => 'local_database_exception',
                    'message' => 'Erreur API. Données de la base locale.'
                ]
            ];
        }

        throw new MoneyFusionException($e->getMessage(), 0, $e);
    }
}
```

### Avantages
- ✅ Continue à fonctionner même si l'API est indisponible
- ✅ Fournit toujours des informations de statut
- ✅ Meilleure expérience utilisateur
- ✅ Résilience accrue

### Impact
- **Sévérité**: Moyenne
- **Affecté**: Tous les utilisateurs
- **Workaround actuel**: `CustomMoneyFusionService` avec fallback

---

## 6. Documentation de l'API Endpoint 📝

### Problème
La documentation dans le fichier de configuration ne mentionne pas le nouveau format d'URL de l'API MoneyFusion.

### Solution

**Fichier**: `config/moneyfusion.php`

```php
return [
    /*
    |--------------------------------------------------------------------------
    | MoneyFusion API URL
    |--------------------------------------------------------------------------
    |
    | L'URL de l'API MoneyFusion pour créer des paiements.
    |
    | NOUVELLE API (Recommandée):
    | Format: https://www.pay.moneyfusion.net/{AppName}/{ApiKey}/pay/
    | Exemple: https://www.pay.moneyfusion.net/MyApp/abc123def456/pay/
    |
    | ANCIENNE API (Toujours supportée):
    | https://api.moneyfusion.net/api/create-payment
    |
    | Pour obtenir votre URL personnalisée:
    | 1. Connectez-vous à https://moneyfusion.net/dashboard
    | 2. Allez dans "FusionPay" → "Paramètres"
    | 3. Copiez votre URL API personnalisée
    |
    */
    'api_url' => env('MONEYFUSION_API_URL', 'https://api.moneyfusion.net/api/create-payment'),

    /*
    |--------------------------------------------------------------------------
    | MoneyFusion Check Payment URL
    |--------------------------------------------------------------------------
    |
    | URL pour vérifier le statut d'un paiement.
    |
    | NOUVELLE API:
    | https://www.pay.moneyfusion.net/paiementNotif
    | Le token sera automatiquement ajouté: /paiementNotif/{token}
    |
    | ANCIENNE API:
    | Laissez vide, l'URL sera construite automatiquement depuis api_url
    |
    */
    'check_payment_url' => env('MONEYFUSION_CHECK_PAYMENT_URL', null),

    /*
    |--------------------------------------------------------------------------
    | MoneyFusion App Key
    |--------------------------------------------------------------------------
    |
    | Votre clé API au format: AppName/ApiKey
    | Exemple: MyApp/abc123def456
    |
    | ⚠️ IMPORTANT:
    | - Gardez cette clé secrète et ne la commitez jamais dans Git
    | - Utilisez des clés différentes pour développement et production
    |
    | Obtenue depuis: https://moneyfusion.net/dashboard/fusionpay
    |
    */
    'app_key' => env('MONEYFUSION_APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | URL où MoneyFusion enverra les notifications de paiement en temps réel.
    |
    | IMPORTANT:
    | - DOIT être en HTTPS avec un domaine valide en production
    | - Pour le développement local, utilisez ngrok ou expose:
    |   Exemple: https://abc123.ngrok-free.app/api/moneyfusion/webhook
    |
    | Format recommandé: {APP_URL}/api/moneyfusion/webhook
    |
    */
    'webhook_url' => env('MONEYFUSION_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Return URL
    |--------------------------------------------------------------------------
    |
    | URL où l'utilisateur sera redirigé après le paiement.
    |
    | IMPORTANT:
    | - DOIT être en HTTPS avec un domaine valide en production
    | - Pour le développement local, utilisez ngrok ou expose:
    |   Exemple: https://abc123.ngrok-free.app/payment/callback
    |
    | Format recommandé: {APP_URL}/payment/callback
    |
    */
    'return_url' => env('MONEYFUSION_RETURN_URL'),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Activer/désactiver la vérification SSL pour les requêtes HTTP.
    |
    | ⚠️ IMPORTANT:
    | - Mettre à false UNIQUEMENT en développement local
    | - TOUJOURS à true en production pour la sécurité
    |
    | Utilisez false si vous rencontrez:
    | "cURL error 60: SSL certificate problem"
    |
    */
    'verify_ssl' => env('MONEYFUSION_VERIFY_SSL', true),
];
```

**Ajouter un fichier README dans le package**:

**Fichier**: `README.md`

```markdown
# Laravel MoneyFusion Integration

## Migration vers la nouvelle API

Si vous utilisez l'ancienne API (`api.moneyfusion.net`), migrez vers la nouvelle:

### Avant
```env
MONEYFUSION_API_URL=https://api.moneyfusion.net/api/create-payment
```

### Après
```env
MONEYFUSION_API_URL=https://www.pay.moneyfusion.net/VotreApp/VotreCleAPI/pay/
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif
```

## Configuration complète

```env
# API MoneyFusion
MONEYFUSION_API_URL=https://www.pay.moneyfusion.net/MyApp/abc123/pay/
MONEYFUSION_APP_KEY=MyApp/abc123
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif

# SSL (false en dev, true en prod)
MONEYFUSION_VERIFY_SSL=false

# Webhooks (utilisez ngrok en développement local)
MONEYFUSION_WEBHOOK_URL=https://your-domain.com/api/moneyfusion/webhook
MONEYFUSION_RETURN_URL=https://your-domain.com/payment/callback
```
```

### Impact
- **Sévérité**: Faible (documentation)
- **Affecté**: Nouveaux utilisateurs
- **Workaround actuel**: Documentation externe

---

## 7. Tests Automatisés 🧪

### Problème
Manque de tests automatisés pour valider les fonctionnalités critiques.

### Solution

**Fichier**: `tests/Feature/PaymentCreationTest.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Simonet85\LaravelMoneyFusion\Tests\TestCase;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

class PaymentCreationTest extends TestCase
{
    protected MoneyFusionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MoneyFusionService::class);
    }

    /** @test */
    public function it_can_create_payment_with_ssl_disabled()
    {
        config(['moneyfusion.verify_ssl' => false]);

        Http::fake([
            '*' => Http::response([
                'statut' => true,
                'token' => 'test123',
                'url' => 'https://payin.moneyfusion.net/payment/test123',
                'message' => 'paiement en cours'
            ], 200)
        ]);

        $result = $this->service->createPayment([
            'total_price' => 5000,
            'articles' => [
                ['name' => 'Test Product', 'price' => 5000, 'quantity' => 1]
            ],
            'nom_client' => 'Test Client',
        ]);

        $this->assertTrue($result['statut']);
        $this->assertEquals('test123', $result['token']);
    }

    /** @test */
    public function it_stores_payment_in_database()
    {
        Http::fake([
            '*' => Http::response([
                'statut' => true,
                'token' => 'test456',
                'url' => 'https://payin.moneyfusion.net/payment/test456',
            ], 200)
        ]);

        $this->service->createPayment([
            'total_price' => 10000,
            'articles' => [['name' => 'Product', 'price' => 10000, 'quantity' => 1]],
            'nom_client' => 'John Doe',
            'numero_send' => '0707080910',
        ]);

        $this->assertDatabaseHas('moneyfusion_payments', [
            'token_pay' => 'test456',
            'nom_client' => 'John Doe',
            'montant' => 10000,
            'statut' => 'pending',
        ]);
    }
}
```

**Fichier**: `tests/Feature/PaymentStatusCheckTest.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Simonet85\LaravelMoneyFusion\Tests\TestCase;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

class PaymentStatusCheckTest extends TestCase
{
    /** @test */
    public function it_falls_back_to_local_database_when_api_fails()
    {
        $payment = MoneyFusionPayment::factory()->create([
            'token_pay' => 'local123',
            'statut' => 'paid',
            'montant' => 5000,
        ]);

        Http::fake([
            '*' => Http::response('Not Found', 404)
        ]);

        $service = app(MoneyFusionService::class);
        $result = $service->checkPaymentStatus('local123');

        $this->assertTrue($result['statut']);
        $this->assertEquals('local_database', $result['data']['source']);
        $this->assertEquals('paid', $result['data']['statut']);
    }

    /** @test */
    public function it_updates_payment_status_from_api()
    {
        $payment = MoneyFusionPayment::factory()->create([
            'token_pay' => 'update123',
            'statut' => 'pending',
        ]);

        Http::fake([
            '*' => Http::response([
                'statut' => true,
                'data' => [
                    'statut' => 'paid',
                    'numeroTransaction' => 'MF123456',
                    'moyen' => 'orange_money',
                    'frais' => 150,
                ]
            ], 200)
        ]);

        $service = app(MoneyFusionService::class);
        $service->checkPaymentStatus('update123');

        $payment->refresh();
        $this->assertEquals('paid', $payment->statut);
        $this->assertEquals('MF123456', $payment->numero_transaction);
        $this->assertNotNull($payment->paid_at);
    }
}
```

**Fichier**: `tests/Feature/WebhookTest.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Tests\Feature;

use Simonet85\LaravelMoneyFusion\Tests\TestCase;
use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;

class WebhookTest extends TestCase
{
    /** @test */
    public function it_handles_webhook_notification()
    {
        $payment = MoneyFusionPayment::factory()->create([
            'token_pay' => 'webhook123',
            'statut' => 'pending',
        ]);

        $response = $this->postJson('/api/moneyfusion/webhook', [
            'token' => 'webhook123',
            'statut' => 'paid',
            'numeroTransaction' => 'MF789',
            'moyen' => 'mtn_money',
            'frais' => 200,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertEquals('paid', $payment->statut);
        $this->assertEquals('MF789', $payment->numero_transaction);
    }

    /** @test */
    public function it_returns_error_when_payment_not_found()
    {
        $response = $this->postJson('/api/moneyfusion/webhook', [
            'token' => 'nonexistent',
            'statut' => 'paid',
        ]);

        $response->assertNotFound();
        $response->assertJson(['error' => 'Payment not found']);
    }
}
```

### Impact
- **Sévérité**: Faible (qualité de code)
- **Affecté**: Développeurs du package
- **Workaround actuel**: Tests manuels

---

## 8. Support de Composer 2.x 📦

### Problème
Le `composer.json` du package limite inutilement les versions de Laravel supportées.

### Solution

**Fichier**: `composer.json`

```json
{
    "name": "simonet85/laravel-moneyfusion",
    "description": "Laravel integration for MoneyFusion payment gateway",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Simonet85",
            "email": "contact@simonet85.com"
        }
    ],
    "require": {
        "php": "^8.1|^8.2|^8.3",
        "illuminate/support": "^9.0|^10.0|^11.0|^12.0",
        "illuminate/http": "^9.0|^10.0|^11.0|^12.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "orchestra/testbench": "^7.0|^8.0|^9.0",
        "phpunit/phpunit": "^9.5|^10.0|^11.0",
        "mockery/mockery": "^1.5"
    },
    "autoload": {
        "psr-4": {
            "Simonet85\\LaravelMoneyFusion\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Simonet85\\LaravelMoneyFusion\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Simonet85\\LaravelMoneyFusion\\MoneyFusionServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### Impact
- **Sévérité**: Haute (compatibilité)
- **Affecté**: Laravel 12 users
- **Workaround actuel**: Aucun nécessaire si installé via composer

---

## 9. Événements pour Webhooks 🔔

### Problème
Pas de moyen d'écouter les changements de statut de paiement pour déclencher des actions personnalisées (envoi d'email, mise à jour de commande, etc.).

### Solution

**Fichier**: `src/Events/PaymentStatusUpdated.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Events;

use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MoneyFusionPayment $payment,
        public string $oldStatus,
        public string $newStatus
    ) {}
}
```

**Fichier**: `src/Events/PaymentReceived.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Events;

use Simonet85\LaravelMoneyFusion\Models\MoneyFusionPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public MoneyFusionPayment $payment) {}
}
```

**Fichier**: `src/Http/Controllers/WebhookController.php`

```php
use Simonet85\LaravelMoneyFusion\Events\PaymentStatusUpdated;
use Simonet85\LaravelMoneyFusion\Events\PaymentReceived;

public function handle(Request $request): JsonResponse
{
    // ... code existant

    $oldStatus = $payment->statut;

    $payment->update([
        'statut' => $data['statut'] ?? 'pending',
        'numero_transaction' => $data['numeroTransaction'] ?? null,
        'moyen' => $data['moyen'] ?? null,
        'frais' => $data['frais'] ?? 0,
    ]);

    // ✅ Déclencher les événements
    if ($oldStatus !== $payment->statut) {
        event(new PaymentStatusUpdated($payment, $oldStatus, $payment->statut));
    }

    if ($payment->statut === 'paid' && $oldStatus !== 'paid') {
        event(new PaymentReceived($payment));
    }

    return response()->json(['success' => true]);
}
```

**Usage par l'utilisateur**:

```php
// Dans app/Providers/EventServiceProvider.php
protected $listen = [
    \Simonet85\LaravelMoneyFusion\Events\PaymentReceived::class => [
        \App\Listeners\SendPaymentConfirmation::class,
        \App\Listeners\FulfillOrder::class,
    ],
];

// Dans app/Listeners/SendPaymentConfirmation.php
public function handle(PaymentReceived $event)
{
    $payment = $event->payment;

    Mail::to($payment->user)->send(
        new PaymentConfirmationMail($payment)
    );
}
```

### Impact
- **Sévérité**: Moyenne (fonctionnalité)
- **Affecté**: Tous les utilisateurs
- **Workaround actuel**: Observer le modèle manuellement

---

## 10. Gestion d'Erreurs Améliorée ⚠️

### Problème
Les exceptions ne fournissent pas assez de contexte pour le débogage.

### Solution

**Fichier**: `src/Exceptions/PaymentCreationException.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Exceptions;

class PaymentCreationException extends MoneyFusionException
{
    public function __construct(
        string $message,
        public ?array $apiResponse = null,
        public ?array $requestData = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return [
            'api_response' => $this->apiResponse,
            'request_data' => $this->requestData,
        ];
    }
}
```

**Fichier**: `src/Exceptions/PaymentNotFoundException.php`

```php
<?php

namespace Simonet85\LaravelMoneyFusion\Exceptions;

class PaymentNotFoundException extends MoneyFusionException
{
    public function __construct(
        public string $token,
        string $message = 'Payment not found',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return ['token' => $this->token];
    }
}
```

**Utilisation**:

```php
// Dans MoneyFusionService
public function createPayment(array $data): array
{
    try {
        $payload = $this->preparePayload($data);
        $response = Http::timeout($this->timeout)->post($this->apiUrl, $payload);

        if (!$response->successful()) {
            throw new PaymentCreationException(
                message: 'Failed to create payment',
                apiResponse: $response->json(),
                requestData: $data
            );
        }

        // ...
    } catch (PaymentCreationException $e) {
        Log::error('Payment creation failed', $e->context());
        throw $e;
    }
}

public function getPaymentByToken(string $tokenPay): MoneyFusionPayment
{
    $payment = MoneyFusionPayment::where('token_pay', $tokenPay)->first();

    if (!$payment) {
        throw new PaymentNotFoundException($tokenPay);
    }

    return $payment;
}
```

### Impact
- **Sévérité**: Faible (développeur expérience)
- **Affecté**: Développeurs utilisant le package
- **Workaround actuel**: Logging manuel

---

## Comment Contribuer

### Option 1: Pull Request

1. **Fork le repository**: https://github.com/simonet85/laravel-moneyfusion
2. **Créer une branche**:
   ```bash
   git checkout -b feature/laravel-12-compatibility
   ```
3. **Implémenter les changements**
4. **Tester**:
   ```bash
   composer test
   ```
5. **Créer un Pull Request** avec une description détaillée

### Option 2: Issue GitHub

Créer une issue avec ce template:

```markdown
# Amélioration: Support Laravel 12 et améliorations diverses

## Problèmes identifiés

1. **Compatibilité Laravel 12**: Classe Controller introuvable
2. **SSL Verification**: Pas d'option pour désactiver en dev
3. **API Endpoint**: Ne supporte pas la nouvelle API MoneyFusion
4. ... (autres points)

## Solutions proposées

[Copier les solutions de ce document]

## Impact

- Nombre d'utilisateurs affectés: Tous ceux sur Laravel 12
- Rétrocompatibilité: Oui, les changements sont backward-compatible
- Breaking changes: Non

## Workarounds actuels

Voir: https://github.com/simonet85/laravel-moneyfusion/issues/XXX

## Références

- Documentation Laravel 12: https://laravel.com/docs/12.x
- MoneyFusion API: https://moneyfusion.net/dashboard/fusionpay
```

---

## Workaround Actuel (Fonctionnel)

En attendant que ces améliorations soient implémentées dans le package, notre application utilise:

✅ **CustomMoneyFusionService** - Gère SSL et fallback
✅ **MoneyFusionCompatibilityServiceProvider** - Résout Laravel 12
✅ **Custom Webhook Controllers** - Gestion complète des webhooks
✅ **Configuration étendue** - Support nouvelle API MoneyFusion

**Fichiers créés**:
- `app/Services/CustomMoneyFusionService.php`
- `app/Providers/CustomMoneyFusionServiceProvider.php`
- `app/Providers/MoneyFusionCompatibilityServiceProvider.php`
- `app/Http/Controllers/MoneyFusion/WebhookController.php`
- `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`

---

## Résumé des Priorités

| Amélioration | Sévérité | Priorité | Impact |
|-------------|----------|----------|--------|
| Compatibilité Laravel 12 | ⚠️ Critique | 1 | Bloquant pour Laravel 12 |
| SSL Verification | 🔴 Haute | 2 | Bloque développement local |
| URL Flexible | 🟡 Moyenne | 3 | Nouvelle API |
| Nom de Table | 🟡 Moyenne | 4 | Confusion utilisateurs |
| Fallback Statut | 🟡 Moyenne | 5 | Résilience |
| Documentation | 🟢 Faible | 6 | Expérience utilisateur |
| Tests | 🟢 Faible | 7 | Qualité code |
| Composer 2.x | 🔴 Haute | 8 | Compatibilité |
| Événements | 🟡 Moyenne | 9 | Extensibilité |
| Exceptions | 🟢 Faible | 10 | DX (Developer Experience) |

---

## Contact

Pour questions ou suggestions:
- GitHub Issues: https://github.com/simonet85/laravel-moneyfusion/issues
- Email: contact@simonet85.com

---

**Dernière mise à jour**: 2025-11-08
**Version du package**: 1.0.1
**Laravel compatible**: 9, 10, 11, (12 avec workaround)
