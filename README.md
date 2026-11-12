<div align="center">
  <img src="public/images/others/logo-white-02.png" alt="Glowing Cosmetics" width="300">

  # Glowing Cosmetics

  Boutique en ligne de cosmétiques — catalogue, commandes, paiements mobiles et back-office complet.

  [![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
  [![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.1-38bdf8.svg)](https://tailwindcss.com)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
</div>

---

## Sommaire

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Rôles](#rôles)
- [Technologies](#technologies)
- [Démarrage rapide (Docker)](#démarrage-rapide-docker)
- [Commandes utiles](#commandes-utiles)
- [Configuration](#configuration)
- [Connexion sans mot de passe](#connexion-sans-mot-de-passe)
- [Paiements](#paiements)
- [Back-office](#back-office)
- [Tests et qualité](#tests-et-qualité)
- [Déploiement](#déploiement)
- [Structure du projet](#structure-du-projet)
- [Documentation](#documentation)
- [Licence](#licence)

---

## À propos

**Glowing Cosmetics** est une boutique e-commerce développée avec **Laravel 12** (Blade + Tailwind CSS). Elle vend des cosmétiques en **franc CFA (FCFA)**, en français par défaut avec un sélecteur FR/EN, et propose un back-office qui couvre le catalogue, les promotions, les commandes, la relation client et les réglages de la boutique.

- Développement local : `http://localhost:8001`
- Production : `https://klab-consulting.com`

---

## Fonctionnalités

### Boutique

- Accueil avec deux présentations au choix (*Classique* ou *Glowing Beauty*), bannières pilotées depuis le back-office, produits mis en vedette et offre à durée limitée.
- Catalogue filtrable : recherche, catégories, tags, prix minimum/maximum, promotions, produits épuisés, formats, tri et pagination.
- Fiche produit : galerie, prix promotionnel, stock, formats, onglets d'informations (utilisation, ingrédients, précautions, conservation) et avis modérés.
- Panier et liste de souhaits (session pour les visiteurs, rattachés au compte après connexion).
- **Commande sans compte** : adresse de livraison, adresse de facturation distincte facultative, code promo et notes, avec protection contre le double envoi.
- Newsletter (inscription, désinscription par lien personnel) et formulaire de contact.

### Comptes clients

- Connexion par mot de passe, **code à usage unique par e-mail**, **code à usage unique par SMS**, connexion sociale facultative (Google/Facebook) et mot de passe oublié.
- Tableau de bord (commandes, total payé), historique et détail des commandes, annulation tant que la commande est en attente (stock restitué automatiquement).
- Profil modifiable (coordonnées, avatar, mot de passe).

### Paiements

- **Mobile money via MoneyFusion** (Orange Money, MTN, Wave…), avec webhook, page de retour et réconciliation automatique des paiements en attente.
- **Paiement à la livraison**, **virement bancaire**, **carte bancaire** et **PayPal**, chacun activable ou non dans les réglages.
- Statuts de commande (en attente, en préparation, expédiée, livrée, annulée) et de paiement (en attente, payé, échoué, remboursé), e-mails de confirmation, d'expédition et facture PDF.

### Back-office

- **Tableau de bord** : revenus du mois, commandes, produits actifs, clients, panneau « À traiter » (commandes en attente, avis à modérer, messages non lus, stock bas), revenus sur six mois, meilleures ventes.
- **Commandes** : filtres, export CSV, actions groupées, mise à jour des statuts, suivi transporteur, vérification du paiement, facture PDF.
- **Produits** : CRUD complet, images multiples, prix et promotion, stock, statut, vedette, catégories, tags et onglets d'informations de la fiche.
- **Catégories**, **coupons** (pourcentage ou montant fixe, panier minimum, plafond, validité, limite d'utilisation, duplication).
- **Bannières** (type, emplacement, ordre, dates, image, lien), **clients**, **avis** (modération simple et groupée).
- **Messages** du formulaire de contact (lu/non lu, réponse par e-mail), **newsletter** (abonnés, export CSV) et **campagnes** e-mail (brouillon, aperçu, envoi par lots, annulation).
- **Réglages** : identité, boutique, e-mail, apparence, moyens de paiement et livraison — sans toucher au code.

---

## Rôles

| Rôle | Accès | Usage |
|---|---|---|
| `customer` | Boutique + `/account` | Achat, favoris, commandes, profil |
| `admin` | `/admin` | Gestion complète, sauf suppression d'un autre administrateur |
| `super_admin` | `/admin` | Comme `admin` ; rôle du compte fondateur |

Les visiteurs non connectés peuvent parcourir la boutique et commander en invité.

---

## Technologies

| Couche | Outils |
|---|---|
| Backend | Laravel 12, PHP ≥ 8.2 (conteneur PHP 8.3-FPM), MySQL 8 |
| Frontend | Blade, Tailwind CSS 4.1, Vite 7, Font Awesome |
| Paiements | MoneyFusion (mobile money) |
| E-mails | Laravel Mail (SMTP), DomPDF pour les factures |
| SMS | Vonage |
| Codes à usage unique | spatie/laravel-one-time-passwords |
| Qualité | PHPUnit, Laravel Pint |
| Déploiement | Docker Compose, script SSH pour hébergement mutualisé Hostinger |

---

## Démarrage rapide (Docker)

Prérequis : Docker et Docker Compose.

```bash
git clone https://github.com/simonet85/cosmetics-app.git
cd cosmetics-app
docker compose up -d --build
```

Au premier démarrage, le conteneur `app` :

1. crée `.env` depuis `.env.example` ;
2. installe les dépendances Composer ;
3. génère `APP_KEY` ;
4. exécute les migrations et crée le lien de stockage.

| Service | Rôle | Accès |
|---|---|---|
| `web` | nginx | http://localhost:8001 |
| `app` | PHP 8.3-FPM (artisan, tests, Composer) | `docker compose exec app …` |
| `db` | MySQL 8 (base `cosmetics_db`, utilisateur `cosmetics`) | `localhost:3308` |
| `node` | Vite (développement des styles) | http://localhost:5173 |
| `queue`, `scheduler` | Workers | profil `workers` : `docker compose --profile workers up -d` |

Charger les données de démonstration (comptes de test inclus) :

```bash
docker compose exec app php artisan db:seed
```

Comptes de démonstration : `admin@cosmetics.test` / `password` (super administrateur) et `client@cosmetics.test` / `password` (cliente).

---

## Commandes utiles

```bash
# Tests et formatage
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint

# Base de données
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed

# Assets (le serveur n'a pas Node : on construit en local)
docker compose run --rm --no-deps --volume /var/www/html/node_modules node sh -c "npm run build"

# Journaux (codes de connexion en développement)
docker compose exec app tail -f storage/logs/laravel.log

# Console interactive
docker compose exec app php artisan tinker
```

> Le volume nommé `vendor` (voir `docker-compose.override.yml`) évite la lenteur du montage Windows sur `vendor/`.

---

## Configuration

Toute la configuration passe par `.env` (non versionné). Les réglages métier se font dans le back-office ; le fichier `.env` ne sert qu'à l'infrastructure.

| Variables | Rôle |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | Environnement, erreurs (`false` en production), URL publique, clé de chiffrement |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Connexion MySQL |
| `MAIL_MAILER` + `MAIL_*` | Envoi des e-mails ; `log` en développement, SMTP en production |
| `VONAGE_KEY`, `VONAGE_SECRET`, `VONAGE_SMS_FROM` | Envoi des SMS ; sans clés, le code est écrit dans le journal (dev uniquement) |
| `MONEYFUSION_APP_KEY`, `MONEYFUSION_API_URL`, `MONEYFUSION_CHECK_PAYMENT_URL`, `MONEYFUSION_WEBHOOK_URL`, `MONEYFUSION_RETURN_URL` | Passerelle mobile money, vérification et notifications |
| `GOOGLE_CLIENT_ID/SECRET`, `FACEBOOK_CLIENT_ID/SECRET` | Connexion sociale (facultative) |
| `SMS_OTP_*`, `EMAIL_OTP_*` | Limites anti-abus des codes (par heure et par numéro/adresse, par IP, délai, validité) |

Après modification en production : `php artisan config:cache`.

---

## Connexion sans mot de passe

| Parcours | URL | Fonctionnement |
|---|---|---|
| Code par e-mail | `/login/email` | Code à 6 chiffres envoyé par e-mail, valable quelques minutes |
| Code par SMS | `/login/sms` | Code par SMS si le numéro est connu et vérifié ; sinon écran d'activation (e-mail, mot de passe, numéro) |
| Mot de passe | `/login` | Formulaire classique |

Des limites anti-abus protègent les formulaires (codes par heure, délai de 60 s entre deux demandes, plafond par IP). En développement, les codes sont écrits dans `storage/logs/laravel.log` — jamais en production.

---

## Paiements

Les cinq moyens sont pilotés par les réglages (`payment_*_enabled`) et contrôlés côté serveur, pas seulement dans le formulaire :

| Moyen | Comportement |
|---|---|
| Mobile money (MoneyFusion) | Redirection vers la page de paiement ; confirmation par webhook/retour ; réconciliation toutes les 10 minutes par le planificateur |
| Paiement à la livraison | Commande immédiate, réglée en espèces |
| Virement bancaire | Instructions communiquées, paiement marqué payé à réception |
| Carte bancaire | Proposée selon la configuration de la passerelle |
| PayPal | Proposé selon la configuration du compte PayPal |

Endpoints MoneyFusion : `POST /api/moneyfusion/webhook` (notification serveur) et `GET /payment/callback` (retour du client). Une commande annulée libère le stock réservé, et un paiement qui arrive après l'annulation d'une commande abandonnée la rétablit automatiquement.

---

## Back-office

Accessible à `/admin` (rôles `admin` et `super_admin`) :

1. Tableau de bord
2. Commandes (export CSV, facture PDF, suivi, vérification du paiement, actions groupées)
3. Clients
4. Produits (fiche complète, images, promotion, stock)
5. Catégories
6. Avis clients (modération)
7. Coupons
8. Bannières
9. Newsletter (abonnés, export CSV)
10. Campagnes e-mail (brouillon → envoi par lots → terminée, annulation possible pendant l'envoi)
11. Messages (réponse par e-mail)
12. Paramètres (Général, Boutique, E-mail, Apparence, Paiement, Livraison)

---

## Tests et qualité

```bash
docker compose exec app php artisan test    # suite complète (PHPUnit)
docker compose exec app vendor/bin/pint     # formatage du code PHP
```

Les tests couvrent la boutique, le back-office, les paiements et les codes à usage unique.

---

## Déploiement

La production tourne sur un hébergement mutualisé Hostinger (pas de Node sur le serveur, planificateur fourni par une tâche cron). Le déploiement est scripté : il vérifie la branche, construit les assets en local, sauvegarde la base, migre, reconstruit les caches et teste les pages publiques.

```bash
scripts/deploy.sh --dry-run   # montre les étapes, ne modifie rien
scripts/deploy.sh             # déploie origin/main
```

Prérequis :

- branche `main` propre et à jour (`origin/main`) ;
- clé SSH autorisée sur le serveur ;
- Docker (ou Node) en local pour `public/build` ;
- cron hPanel exécutant `php artisan schedule:run` **chaque minute**.

Ligne cron (hPanel > Avancé > Tâches Cron) :

```bash
* * * * * cd ~/domains/klab-consulting.com/laravel && php artisan schedule:run >> ~/domains/klab-consulting.com/laravel/storage/logs/scheduler.log 2>&1
```

Hostinger désactive `proc_open`, `exec` et `shell_exec` : les tâches planifiées sont donc déclarées avec `Schedule::call(fn () => Artisan::call(...))`, exécuté dans le processus courant, et doivent tenir en moins de 60 secondes.

Tâches planifiées :

- vérification des paiements MoneyFusion en attente (toutes les 10 minutes, plus une passe nocturne à 03:00) ; une commande abandonnée depuis plus de 24 h est annulée et son stock libéré ;
- envoi des campagnes newsletter par lots de 20 (chaque minute) ;
- suppression des codes de connexion expirés (nuit).

En cas d'échec après la mise à jour du code, le script affiche la commande de retour arrière (code et assets). La base n'est pas restaurée automatiquement : réimporter le dump indiqué si une migration a échoué.

Lancer la vérification à la main : `php artisan moneyfusion:check-pending --age=10 --limit=100 --expire-after=1440` (`--expire-after=0` désactive l'annulation des paiements abandonnés).

Le détail pas à pas (préparation du serveur, planificateur, retour arrière, checklist) se trouve dans le [guide d'utilisation](docs/guide-utilisation.html#deploiement).

---

## Structure du projet

```
app/
  Http/Controllers/        Contrôleurs boutique, compte et admin
  Http/Controllers/Admin/  Écrans du back-office
  Models/                  Produits, commandes, coupons, bannières, campagnes…
  Services/                Workflow commandes, paiements, expéditions
resources/
  css/app.css              Thème Tailwind (boutique + admin)
  views/storefront/        Boutique publique
  views/admin/             Back-office
  views/components/admin/  Composants du back-office (panneaux, champs, boutons…)
routes/web.php             Routes publiques, compte et admin
database/                  Migrations, seeders et données de démonstration
docker/                    nginx, MySQL, entrypoint
scripts/deploy.sh          Déploiement en production
docs/                      Guide d'utilisation
```

---

## Documentation

Le [guide d'utilisation](docs/guide-utilisation.html) détaille, acteur par acteur :

- les parcours visiteur et client ;
- chaque écran du back-office ;
- chaque réglage et son effet ;
- l'installation locale, le déploiement, la sécurité et le dépannage.

Ouvrez `docs/guide-utilisation.html` dans un navigateur (aucune dépendance, imprimable).

---

## Licence

MIT — voir [LICENSE](LICENSE).
