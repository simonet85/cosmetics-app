<div align="center">
  <img src="public/images/others/logo-white-02.png" alt="Glowing Cosmetics" width="300">

  # Glowing Cosmetics E-commerce Platform

  Une plateforme e-commerce moderne et élégante pour la vente de produits cosmétiques, développée avec Laravel 12.

  [![Laravel](https://img.shields.io/badge/Laravel-12.35.1-red.svg)](https://laravel.com)
  [![PHP](https://img.shields.io/badge/PHP-8.2.28-blue.svg)](https://php.net)
  [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
</div>

---

## 📋 Table des matières

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Déploiement](#déploiement)
- [Structure du projet](#structure-du-projet)
- [API et Intégrations](#api-et-intégrations)

---

## 🌟 À propos

**Glowing Cosmetics** est une application e-commerce complète développée en Laravel 12, spécialisée dans la vente de produits cosmétiques. La plateforme offre une expérience utilisateur fluide avec un design moderne utilisant Tailwind CSS, un système de paiement sécurisé via MoneyFusion, et un panneau d'administration complet pour la gestion des produits, commandes et clients.

---

## ✨ Fonctionnalités

### 🛍️ Côté Client
- **Catalogue de produits** avec filtres et recherche avancée
- **Variantes de produits** (tailles, couleurs, types)
- **Panier d'achat** avec gestion des quantités
- **Liste de souhaits** pour sauvegarder les produits favoris
- **Système d'avis et notes** pour les produits
- **Checkout simplifié** avec processus en une page
- **Gestion de compte** (profil, commandes, adresses)
- **Processus de commande invité** sans inscription obligatoire
- **Carousel Slick** pour l'affichage des produits
- **Design responsive** optimisé pour mobile et desktop

### 💳 Paiements
- **Intégration MoneyFusion** pour les paiements en ligne
- **Paiement à la livraison** (Cash on Delivery)
- **Virement bancaire** avec instructions par email
- **Gestion SSL configurable** pour les requêtes de paiement
- **Webhooks et callbacks** pour les notifications de paiement
- **Suivi des statuts de paiement** en temps réel

### 📧 Notifications
- **Emails de confirmation de commande** avec facture PDF jointe
- **Notifications de statut de commande** (en traitement, expédiée, livrée)
- **Confirmation de paiement** automatique
- **Instructions de virement bancaire** pour les paiements différés
- **Génération de factures PDF** avec DomPDF

### 👨‍💼 Panneau d'administration
- **Gestion complète des produits** (CRUD, images, variantes)
- **Gestion des commandes** avec filtres et recherche
- **Gestion des utilisateurs** avec système de rôles (Spatie Permissions)
- **Gestion des catégories** de produits
- **Gestion des avis clients** et modération
- **Tableau de bord** avec statistiques
- **Mise à jour des statuts** de commande et paiement

### 🔐 Sécurité
- **Authentification Laravel** avec sessions sécurisées
- **Gestion des rôles et permissions** (admin, super_admin, customer)
- **Protection CSRF** sur tous les formulaires
- **Validation des données** côté serveur
- **Hashage des mots de passe** avec Bcrypt
- **Vérification SSL** configurable pour les paiements

---

## 🛠️ Technologies

### Backend
- **Laravel 12.35.1** - Framework PHP
- **PHP 8.2.28** - Langage serveur
- **MySQL 8.0.30** - Base de données
- **Spatie Laravel Permission** - Gestion des rôles
- **DomPDF** - Génération de PDF

### Frontend
- **Blade Templates** - Moteur de templates Laravel
- **Tailwind CSS** - Framework CSS utility-first
- **Alpine.js** - Framework JavaScript léger
- **Font Awesome** - Icônes
- **Slick Carousel** - Carrousel de produits
- **Vite** - Build tool moderne

### Intégrations
- **MoneyFusion** - Passerelle de paiement
- **Laravel Mail** - Système d'envoi d'emails

---

## 📦 Prérequis

- **PHP** >= 8.2.28
- **Composer** >= 2.x
- **Node.js** >= 18.x et **npm** >= 9.x
- **MySQL** >= 8.0 ou **MariaDB** >= 10.3
- **Extension PHP**: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD

---

## 🚀 Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-username/cosmetics-app.git
cd cosmetics-app
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances JavaScript

```bash
npm install
```

### 4. Créer le fichier d'environnement

```bash
cp .env.example .env
```

### 5. Générer la clé d'application

```bash
php artisan key:generate
```

### 6. Configurer la base de données

Créez une base de données MySQL et mettez à jour le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cosmetics_db
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Exécuter les migrations

```bash
php artisan migrate
```

### 8. Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 9. Seed la base de données (optionnel)

```bash
php artisan db:seed
```

### 10. Compiler les assets

```bash
# Développement
npm run dev

# Production
npm run build
```

### 11. Démarrer le serveur de développement

```bash
php artisan serve
```

L'application sera accessible sur `http://localhost:8000`

---

## ⚙️ Configuration

### Configuration MoneyFusion

Ajoutez vos identifiants MoneyFusion dans le fichier `.env` :

```env
MONEYFUSION_API_KEY=votre_cle_api
MONEYFUSION_MERCHANT_ID=votre_merchant_id
MONEYFUSION_SANDBOX=true  # false pour production
MONEYFUSION_SSL_VERIFY=true  # Vérification SSL (true en production)
```

### Configuration Email

Configurez votre service d'email dans `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@glowingcosmetics.com
MAIL_FROM_NAME="Glowing Cosmetics"
```

### Configuration de l'application

```env
APP_NAME="Glowing Cosmetics"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com
APP_TIMEZONE=Africa/Kinshasa
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
```

---

## 🌐 Déploiement

### Déploiement sur serveur de production (Hostinger)

#### 1. Pousser les modifications sur GitHub

```bash
git add .
git commit -m "Description des modifications"
git push origin main
```

#### 2. Se connecter au serveur et tirer les modifications

```bash
ssh -p 65002 u104407086@82.25.113.207
cd ~/domains/klab-consulting.com/laravel
git pull origin main
```

#### 3. Installer les dépendances et compiler les assets

```bash
composer install --optimize-autoloader --no-dev
npm ci
npm run build
```

#### 4. Vider tous les caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 5. Optimiser pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 6. Exécuter les migrations (si nécessaire)

```bash
php artisan migrate --force
```

#### 7. Définir les permissions appropriées

```bash
chmod -R 755 storage bootstrap/cache
```

### Points de vigilance en production

- Toujours définir `APP_DEBUG=false`
- Activer `MONEYFUSION_SSL_VERIFY=true`
- Utiliser des mots de passe forts pour la base de données
- Configurer HTTPS avec un certificat SSL valide
- Sauvegarder régulièrement la base de données
- Surveiller les logs dans `storage/logs/`

---

## 📁 Structure du projet

```
cosmetics-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Contrôleurs admin
│   │   │   ├── AuthController.php
│   │   │   ├── CheckoutController.php
│   │   │   └── ...
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── ...
│   └── Mail/                    # Classes d'emails
├── database/
│   ├── migrations/              # Migrations de la base de données
│   └── seeders/                 # Seeders de données
├── public/
│   ├── images/
│   │   ├── products/           # Images de produits
│   │   ├── avatars/            # Avatars utilisateurs
│   │   └── others/             # Logos et images statiques
│   └── storage/                # Lien symbolique vers storage
├── resources/
│   ├── views/
│   │   ├── admin/              # Vues administration
│   │   ├── checkout/           # Vues processus de commande
│   │   ├── account/            # Vues compte utilisateur
│   │   ├── emails/             # Templates d'emails
│   │   └── layouts/            # Layouts Blade
│   └── css/
│       └── app.css             # Styles Tailwind
├── routes/
│   ├── web.php                 # Routes web
│   └── api.php                 # Routes API
├── storage/
│   ├── app/
│   │   └── public/             # Fichiers publics uploadés
│   └── logs/                   # Logs de l'application
├── vendor/                     # Dépendances PHP
├── .env                        # Configuration environnement
├── composer.json               # Dépendances Composer
└── package.json                # Dépendances npm
```

---

## 🔌 API et Intégrations

### MoneyFusion Webhook

Le système gère automatiquement les webhooks MoneyFusion pour mettre à jour les statuts de paiement :

**Endpoint**: `POST /moneyfusion/webhook`

Le webhook reçoit les notifications de paiement et met à jour :
- Le statut de paiement de la commande
- Le statut de la commande (pending → processing)
- Envoie un email de confirmation de paiement avec facture PDF

### MoneyFusion Callback

Le système gère également les callbacks de retour après paiement :

**Endpoint**: `GET /moneyfusion/callback`

Redirige l'utilisateur vers la page de succès ou d'échec selon le résultat du paiement.

---

## 📝 Fonctionnalités du checkout

Le processus de checkout a été simplifié pour améliorer l'expérience utilisateur :

### Champs requis
- **Prénom** et **Nom**
- **Email** et **Téléphone**
- **Ville** et **Quartier** (remplace l'adresse complète)

### Méthodes de paiement
1. **MoneyFusion** - Paiement en ligne sécurisé
2. **Paiement à la livraison** - Cash on Delivery
3. **Virement bancaire** - Instructions envoyées par email

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

---

## 👤 Auteur

**Glowing Cosmetics Team**

- Website: [https://klab-consulting.com](https://klab-consulting.com)
- GitHub: [@votre-username](https://github.com/votre-username)

---

## 🙏 Remerciements

- [Laravel](https://laravel.com) - Framework PHP
- [Tailwind CSS](https://tailwindcss.com) - Framework CSS
- [MoneyFusion](https://moneyfusion.com) - Passerelle de paiement
- [Font Awesome](https://fontawesome.com) - Icônes
- [Spatie](https://spatie.be) - Packages Laravel

---

<div align="center">
  Développé avec ❤️ par l'équipe Glowing Cosmetics
</div>
