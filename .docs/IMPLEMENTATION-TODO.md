# Implémentations à réaliser - MoneyFusion

## Statut des fonctionnalités

- ✅ **Complété** : Fonctionnalité déjà implémentée
- 🔄 **En cours** : Implémentation commencée
- 📋 **À faire** : Fonctionnalité planifiée mais non implémentée
- 💡 **Optionnel** : Fonctionnalité recommandée mais pas critique

---

## 1. Vérification du statut des paiements

### 📋 Commande Artisan pour vérification automatique

**Priorité** : 🔥 Haute
**Fichier** : `app/Console/Commands/CheckPendingPayments.php`

#### Description
Commande Artisan pour vérifier automatiquement le statut de tous les paiements en attente.

#### Fonctionnalités
- Vérifier les paiements en attente de plus de X minutes
- Limiter le nombre de paiements à vérifier
- Afficher une barre de progression
- Générer un rapport détaillé
- Mettre à jour automatiquement les commandes

#### Commande

```bash
# Vérifier les paiements en attente de plus de 30 minutes (par défaut)
php artisan moneyfusion:check-pending

# Vérifier les paiements de plus de 10 minutes
php artisan moneyfusion:check-pending --age=10

# Limiter à 20 paiements
php artisan moneyfusion:check-pending --limit=20
```

#### Configuration Scheduler

Dans `app/Console/Kernel.php` :

```php
// Vérifier les paiements en attente toutes les 10 minutes
$schedule->command('moneyfusion:check-pending --age=10')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Vérifier les anciens paiements (> 24h) une fois par jour
$schedule->command('moneyfusion:check-pending --age=1440 --limit=500')
    ->daily()
    ->at('03:00');
```

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#commande-artisan-pour-vérification-automatique)

---

### 📋 Vérification AJAX sur la page "Mes commandes"

**Priorité** : 🟡 Moyenne
**Fichiers** :
- Route : `routes/web.php`
- Contrôleur : `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`
- Vue : `resources/views/account/orders/show.blade.php`

#### Description
Permettre aux utilisateurs de vérifier manuellement le statut de leur paiement via un bouton AJAX.

#### Fonctionnalités
- Bouton "Vérifier maintenant" sur la page de détail de commande
- Vérification automatique toutes les 30 secondes si paiement en attente
- Affichage du résultat en temps réel sans rechargement de page
- Animation de chargement pendant la vérification

#### Route à ajouter

```php
// Check payment status (AJAX)
Route::get('/payment/check-status/{token}', [PaymentCallbackController::class, 'checkStatus'])
    ->name('payment.check-status');
```

#### Méthode contrôleur à ajouter

```php
public function checkStatus(Request $request, string $token)
{
    // Voir code complet dans MONEYFUSION-CHECK-PAYMENT.md
}
```

#### Modifications de la vue
Ajouter le widget de vérification dans `resources/views/account/orders/show.blade.php`

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#2-vérifier-le-statut-depuis-la-page-de-commande-utilisateur)

---

### 📋 Interface admin pour vérification manuelle

**Priorité** : 🟡 Moyenne
**Fichiers** :
- Route : `routes/web.php`
- Contrôleur : `app/Http/Controllers/Admin/AdminOrderController.php`
- Vue : `resources/views/admin/orders/show.blade.php`

#### Description
Ajouter un bouton dans l'interface admin pour vérifier manuellement le statut d'un paiement.

#### Fonctionnalités
- Bouton "Vérifier le paiement" sur la page de détail de commande
- Affichage des informations du paiement (token, date de création)
- Messages de succès/erreur clairs
- Mise à jour automatique de la commande après vérification

#### Route à ajouter

```php
Route::post('/admin/orders/{order}/check-payment', [AdminOrderController::class, 'checkPayment'])
    ->name('admin.orders.check-payment');
```

#### Méthode contrôleur à ajouter

```php
public function checkPayment(Order $order, CustomMoneyFusionService $moneyFusion)
{
    // Voir code complet dans MONEYFUSION-CHECK-PAYMENT.md
}
```

#### Modifications de la vue
Ajouter le widget de vérification dans `resources/views/admin/orders/show.blade.php`

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#interface-admin-pour-vérification-manuelle)

---

### 💡 Job pour vérification en arrière-plan

**Priorité** : 🟢 Basse (optionnel)
**Fichier** : `app/Jobs/CheckMoneyFusionPaymentJob.php`

#### Description
Job Laravel pour vérifier un paiement de manière asynchrone via la queue.

#### Fonctionnalités
- Vérification asynchrone (pas de ralentissement)
- Retry automatique en cas d'échec (3 tentatives)
- Backoff progressif (1min, 2min, 5min)
- Logging détaillé

#### Utilisation

```php
// Dispatch immédiatement
CheckMoneyFusionPaymentJob::dispatch($tokenPay);

// Dispatch avec délai (vérifier après 5 minutes)
CheckMoneyFusionPaymentJob::dispatch($tokenPay)->delay(now()->addMinutes(5));
```

#### Configuration requise
- Queue worker actif : `php artisan queue:work`
- Ou supervisor en production

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#job-pour-vérification-en-arrière-plan)

---

### 💡 API endpoint pour vérification

**Priorité** : 🟢 Basse (optionnel)
**Fichiers** :
- Route : `routes/api.php`
- Contrôleur : `app/Http/Controllers/Api/ApiPaymentController.php`

#### Description
Endpoint API REST pour vérifier le statut d'un paiement (pour intégrations externes).

#### Fonctionnalités
- Authentification Laravel Sanctum
- Format JSON standard
- Vérification des permissions utilisateur
- Gestion d'erreurs robuste

#### Route à ajouter

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/payments/{token}/check', [ApiPaymentController::class, 'checkStatus']);
});
```

#### Utilisation

```bash
curl -X GET https://klab-consulting.com/api/payments/{token}/check \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#api-endpoint-pour-vérification)

---

### 📋 Amélioration du callback avec vérification

**Priorité** : 🟡 Moyenne
**Fichier** : `app/Http/Controllers/MoneyFusion/PaymentCallbackController.php`

#### Description
Améliorer le callback utilisateur pour vérifier le statut en temps réel avant la redirection.

#### Avantages
- Garantit l'affichage du bon statut
- Évite les incohérences si le webhook échoue
- Meilleure expérience utilisateur

#### Modifications

```php
public function callback(Request $request)
{
    $token = $request->get('token');
    $payment = MoneyFusionPayment::where('token_pay', $token)->first();

    // ✨ NOUVEAU: Vérifier le statut en temps réel
    try {
        $statusCheck = $this->moneyFusion->checkPaymentStatus($token);
        $payment->refresh();
    } catch (\Exception $e) {
        Log::warning('Callback: Status check failed, using local data', [
            'error' => $e->getMessage()
        ]);
    }

    // ... reste du code ...
}
```

#### Code complet
Voir [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md#1-vérifier-un-paiement-dans-le-callback)

---

## 2. Modifications du package MoneyFusion (Packagist)

### 💡 Solution 1 : Routes optionnelles (Recommandée)

**Priorité** : 🟢 Basse (amélioration package)
**Repository** : `https://github.com/simonet85/laravel-moneyfusion`

#### Description
Rendre l'enregistrement automatique des routes optionnel pour éviter les conflits.

#### Fichiers à modifier
1. `config/moneyfusion.php` - Ajouter l'option `register_routes`
2. `src/MoneyFusionServiceProvider.php` - Conditionner l'enregistrement des routes

#### Configuration

```env
# Dans .env du projet Laravel
MONEYFUSION_REGISTER_ROUTES=false
```

#### Avantages
- Flexibilité maximale pour les développeurs
- Pas de conflit de routes
- Permet de définir des routes personnalisées

#### Version bump suggéré
`1.0.x → 1.1.0` (ajout de fonctionnalité)

#### Code complet
Voir [MONEYFUSION-IMPLEMENTATION.md](../MONEYFUSION-IMPLEMENTATION.md#solution-1--routes-optionnelles-recommandée)

---

### 💡 Solution 2 : Préfixe configurable

**Priorité** : 🟢 Basse (amélioration package)

#### Description
Permettre de personnaliser le préfixe de route du webhook.

#### Configuration

```env
MONEYFUSION_WEBHOOK_PATH=custom/webhook/path
```

#### Code complet
Voir [MONEYFUSION-IMPLEMENTATION.md](../MONEYFUSION-IMPLEMENTATION.md#solution-2--préfixe-configurable)

---

### 💡 Solution 3 : Helper pour l'URL de webhook

**Priorité** : 🟢 Basse (amélioration package)

#### Description
Fournir une fonction helper `moneyfusion_webhook_url()` pour générer l'URL dynamiquement.

#### Utilisation

```php
$payload = [
    'webhook_url' => moneyfusion_webhook_url(),
];
```

#### Code complet
Voir [MONEYFUSION-IMPLEMENTATION.md](../MONEYFUSION-IMPLEMENTATION.md#solution-3--helper-pour-lurl-de-webhook)

---

## 3. Améliorations de l'interface utilisateur

### 📋 Page de statut "Paiement en cours"

**Priorité** : 🟡 Moyenne
**Fichier** : `resources/views/checkout/pending.blade.php`

#### Description
Créer une page intermédiaire pour les paiements en cours de traitement.

#### Fonctionnalités
- Affichage du statut "en cours"
- Vérification automatique en AJAX
- Redirection automatique quand le paiement est confirmé
- Animation de chargement

#### Route à ajouter

```php
Route::get('/checkout/pending/{order}', [CheckoutController::class, 'pending'])
    ->name('checkout.pending');
```

---

### 📋 Notifications utilisateur par email

**Priorité** : 🟡 Moyenne
**Fichiers** :
- `app/Notifications/PaymentConfirmedNotification.php`
- `app/Notifications/PaymentFailedNotification.php`

#### Description
Envoyer des emails automatiques lors des changements de statut de paiement.

#### Fonctionnalités
- Email de confirmation de paiement (avec facture PDF)
- Email d'échec de paiement (avec lien pour réessayer)
- Email de paiement en attente (après X minutes)

#### Déclenchement
À ajouter dans le `WebhookController` :

```php
if ($data['statut'] === 'paid') {
    $order->user->notify(new PaymentConfirmedNotification($order));
}
```

---

## 4. Monitoring et reporting

### 💡 Dashboard admin des paiements

**Priorité** : 🟢 Basse (optionnel)
**Fichiers** :
- Route : `routes/web.php`
- Contrôleur : `app/Http/Controllers/Admin/PaymentDashboardController.php`
- Vue : `resources/views/admin/payments/dashboard.blade.php`

#### Description
Page de tableau de bord pour visualiser tous les paiements MoneyFusion.

#### Fonctionnalités
- Statistiques des paiements (réussis, échoués, en attente)
- Graphiques temporels
- Liste des paiements récents
- Filtres par statut, date, montant
- Export CSV

---

### 💡 Commande de rapport des paiements

**Priorité** : 🟢 Basse (optionnel)
**Fichier** : `app/Console/Commands/PaymentReportCommand.php`

#### Description
Générer un rapport des paiements pour une période donnée.

#### Utilisation

```bash
# Rapport du jour
php artisan moneyfusion:report --date=today

# Rapport du mois
php artisan moneyfusion:report --date=month

# Rapport personnalisé
php artisan moneyfusion:report --from=2025-01-01 --to=2025-01-31
```

---

## 5. Tests et qualité

### 💡 Tests unitaires

**Priorité** : 🟢 Basse (recommandé)
**Fichiers** :
- `tests/Unit/Services/CustomMoneyFusionServiceTest.php`
- `tests/Feature/MoneyFusion/PaymentFlowTest.php`

#### Description
Tests pour valider le bon fonctionnement de l'intégration MoneyFusion.

#### Tests à créer
- Test de création de paiement
- Test de vérification de statut
- Test du callback
- Test du webhook
- Test de la commande de vérification

---

## Plan de déploiement

### Phase 1 : Fonctionnalités critiques 🔥

1. ✅ Commande Artisan de vérification (`CheckPendingPayments`)
2. ✅ Configuration du Scheduler (toutes les 10 minutes)
3. ✅ Amélioration du callback avec vérification en temps réel

**Temps estimé** : 2-3 heures
**Impact** : Résout les problèmes de synchronisation des paiements

---

### Phase 2 : Interface utilisateur 🟡

1. ✅ Vérification AJAX sur "Mes commandes"
2. ✅ Interface admin de vérification manuelle
3. ✅ Page "Paiement en cours"

**Temps estimé** : 3-4 heures
**Impact** : Améliore l'expérience utilisateur

---

### Phase 3 : Notifications 🟡

1. ✅ Email de confirmation de paiement
2. ✅ Email d'échec de paiement
3. ✅ Email de paiement en attente

**Temps estimé** : 2-3 heures
**Impact** : Communication proactive avec les utilisateurs

---

### Phase 4 : Améliorations optionnelles 🟢

1. ✅ Job de vérification en arrière-plan
2. ✅ API endpoint
3. ✅ Dashboard admin des paiements
4. ✅ Tests unitaires

**Temps estimé** : 4-6 heures
**Impact** : Qualité, monitoring, intégrations

---

### Phase 5 : Contribution au package 🟢

1. ✅ Implémenter routes optionnelles
2. ✅ Soumettre Pull Request au repository
3. ✅ Mettre à jour la documentation

**Temps estimé** : 2-3 heures
**Impact** : Contribution open-source, améliore le package pour tous

---

## Configuration de production recommandée

### Variables d'environnement

```env
# MoneyFusion Configuration
MONEYFUSION_API_URL=https://api.moneyfusion.net/api/create-payment
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif
MONEYFUSION_APP_KEY=YourApp/YourApiKey
MONEYFUSION_VERIFY_SSL=true
MONEYFUSION_LOGGING_ENABLED=true

# Webhook et Return URL
MONEYFUSION_WEBHOOK_URL=https://klab-consulting.com/api/moneyfusion/webhook
MONEYFUSION_RETURN_URL=https://klab-consulting.com/payment/callback

# Optional (si package modifié)
MONEYFUSION_REGISTER_ROUTES=false
```

### Cron jobs

```bash
# Ajouter dans crontab (Hostinger)
*/10 * * * * cd ~/domains/klab-consulting.com/laravel && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduler Laravel

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Vérifier les paiements en attente toutes les 10 minutes
    $schedule->command('moneyfusion:check-pending --age=10 --limit=100')
        ->everyTenMinutes()
        ->withoutOverlapping()
        ->runInBackground();

    // Vérifier les anciens paiements (> 24h) une fois par jour
    $schedule->command('moneyfusion:check-pending --age=1440 --limit=500')
        ->daily()
        ->at('03:00');

    // Générer un rapport quotidien
    $schedule->command('moneyfusion:report --date=yesterday --email=admin@example.com')
        ->dailyAt('04:00');
}
```

---

## Notes importantes

### Sécurité

- ✅ CSRF désactivé uniquement pour le webhook
- ✅ SSL toujours activé en production
- ✅ Validation des tokens avant vérification
- ✅ Logging de toutes les opérations sensibles

### Performance

- ⚠️ Limiter le nombre de vérifications simultanées (option `--limit`)
- ⚠️ Utiliser `withoutOverlapping()` pour éviter les doublons
- ⚠️ Ajouter des pauses entre les requêtes API (`usleep(200000)`)

### Monitoring

- 📊 Surveiller les logs : `storage/logs/laravel.log`
- 📊 Vérifier les métriques de paiements (succès/échecs)
- 📊 Alertes en cas de taux d'échec élevé

---

## Ressources

### Documentation
- [MONEYFUSION-IMPLEMENTATION.md](../MONEYFUSION-IMPLEMENTATION.md) - Documentation complète de l'intégration
- [MONEYFUSION-CHECK-PAYMENT.md](../MONEYFUSION-CHECK-PAYMENT.md) - Guide de vérification des paiements

### Repositories
- Application : `https://github.com/simonet85/cosmetics-app`
- Package : `https://github.com/simonet85/laravel-moneyfusion`

### API
- MoneyFusion API : `https://api.moneyfusion.net`
- Check Payment : `https://www.pay.moneyfusion.net/paiementNotif/{token}`
- Documentation : `https://docs.moneyfusion.net`

---

**Document créé le** : 2025-11-19
**Dernière mise à jour** : 2025-11-19
**Version** : 1.0
**Status** : 📋 Planifié
