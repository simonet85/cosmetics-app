# Guide de Déploiement sur Hostinger

## Informations de connexion

### Base de données MySQL
- **Nom de la base**: `u104407086_cosmetics_db`
- **Utilisateur**: `u104407086_root`
- **Mot de passe**: `2YSDR|EF^8c`
- **Host**: `localhost` (ou fourni par Hostinger)

### SSH
- **IP**: `82.25.113.207`
- **Port**: `65002`
- **Utilisateur**: `u104407086`
- **Commande de connexion**:
  ```bash
  ssh -p 65002 u104407086@82.25.113.207
  ```

## Étapes de déploiement

### 1. Préparer le projet localement

#### a. Mettre à jour le fichier .env pour la production
```bash
# Dans votre projet local
cp .env .env.production
```

Modifier `.env.production` avec les paramètres de production:
```env
APP_NAME="Glowing Cosmetics"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_SERA_GENEREE
APP_DEBUG=false
APP_URL=https://votre-domaine.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u104407086_cosmetics_db
DB_USERNAME=u104407086_root
DB_PASSWORD=2YSDR|EF^8c

# MoneyFusion
MONEYFUSION_API_URL=https://www.pay.moneyfusion.net/Glowing/e1212e46fa987add/pay/
MONEYFUSION_CHECK_PAYMENT_URL=https://www.pay.moneyfusion.net/paiementNotif
MONEYFUSION_VERIFY_SSL=true
MONEYFUSION_TIMEOUT=30

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

#### b. Compiler les assets pour production
```bash
# Dans votre projet local
npm run build
```

#### c. Créer une archive du projet
```bash
# Exclure les dossiers inutiles
# Option 1: Utiliser Git (recommandé)
git archive --format=zip --output=cosmetics-app.zip HEAD

# Option 2: Créer manuellement une archive ZIP
# Exclure: node_modules/, .git/, storage/logs/*, storage/framework/sessions/*
# Inclure: vendor/ (ou l'installer sur le serveur)
```

### 2. Se connecter au serveur via SSH

```bash
ssh -p 65002 u104407086@82.25.113.207
```

### 3. Configuration sur le serveur

#### a. Naviguer vers le répertoire racine
```bash
cd ~
```

#### b. Créer la structure des dossiers
```bash
# Le projet Laravel sera dans ~/laravel
# Le dossier public pointera vers ~/public_html
mkdir -p laravel
cd laravel
```

#### c. Uploader le projet
Deux options:

**Option 1: Via SCP (depuis votre machine locale)**
```bash
scp -P 65002 cosmetics-app.zip u104407086@82.25.113.207:~/laravel/
```

**Option 2: Via le gestionnaire de fichiers Hostinger**
- Uploader le fichier ZIP via l'interface web
- Déplacer le fichier dans ~/laravel/

#### d. Décompresser le projet
```bash
cd ~/laravel
unzip cosmetics-app.zip
rm cosmetics-app.zip
```

### 4. Configuration de l'environnement

#### a. Installer Composer (si non installé)
```bash
# Vérifier si Composer est installé
composer --version

# Si non installé, télécharger Composer
cd ~
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### b. Installer les dépendances PHP
```bash
cd ~/laravel
composer install --optimize-autoloader --no-dev
```

#### c. Configurer les permissions
```bash
chmod -R 755 ~/laravel
chmod -R 775 ~/laravel/storage
chmod -R 775 ~/laravel/bootstrap/cache
```

#### d. Configurer le fichier .env
```bash
cp .env.production .env
php artisan key:generate
```

### 5. Configurer la base de données

#### a. Importer la base de données

**Option 1: Via phpMyAdmin (Hostinger)**
- Se connecter à phpMyAdmin depuis le panneau Hostinger
- Sélectionner la base `u104407086_cosmetics_db`
- Importer le fichier SQL exporté depuis votre base locale

**Option 2: Via ligne de commande**
```bash
# Depuis votre machine locale, exporter la base
cd c:\laragon\www\cosmetics-app
"c:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe" -u root cosmetics_db > cosmetics_db.sql

# Uploader vers le serveur
scp -P 65002 cosmetics_db.sql u104407086@82.25.113.207:~/

# Sur le serveur, importer
mysql -u u104407086_root -p'2YSDR|EF^8c' u104407086_cosmetics_db < ~/cosmetics_db.sql
rm ~/cosmetics_db.sql
```

#### b. Exécuter les migrations (si nécessaire)
```bash
cd ~/laravel
php artisan migrate --force
```

### 6. Configurer le dossier public_html

#### a. Vider le dossier public_html
```bash
cd ~/public_html
rm -rf *
rm -rf .[!.]*
```

#### b. Créer un lien symbolique OU copier le contenu
```bash
# Option 1: Lien symbolique (recommandé)
ln -s ~/laravel/public/* ~/public_html/
ln -s ~/laravel/public/.htaccess ~/public_html/.htaccess

# Option 2: Copier le contenu
cp -R ~/laravel/public/* ~/public_html/
cp ~/laravel/public/.htaccess ~/public_html/.htaccess
```

#### c. Créer un fichier index.php personnalisé dans public_html
```bash
cat > ~/public_html/index.php << 'EOFMARKER'
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Déterminer le chemin vers l'application Laravel
$appPath = dirname(__DIR__) . '/laravel';

// Autoloader Composer
require $appPath.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $appPath.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
EOFMARKER
```

#### d. Modifier le fichier .htaccess
```bash
cat > ~/public_html/.htaccess << 'EOFMARKER'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOFMARKER
```

### 7. Optimiser Laravel pour la production

```bash
cd ~/laravel

# Mettre en cache la configuration
php artisan config:cache

# Mettre en cache les routes
php artisan route:cache

# Mettre en cache les vues
php artisan view:cache

# Optimiser l'autoloader
composer dump-autoload -o
```

### 8. Configurer les tâches planifiées (Cron Jobs)

Dans le panneau Hostinger, ajouter un cron job:
```bash
* * * * * cd ~/laravel && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Vérifications finales

#### a. Vérifier les permissions
```bash
# Storage doit être accessible en écriture
chmod -R 775 ~/laravel/storage
chmod -R 775 ~/laravel/bootstrap/cache
```

#### b. Vérifier les logs
```bash
tail -f ~/laravel/storage/logs/laravel.log
```

#### c. Tester l'application
Visitez votre domaine dans le navigateur et vérifiez:
- [ ] La page d'accueil se charge
- [ ] Les assets (CSS/JS/images) se chargent
- [ ] Le checkout fonctionne
- [ ] MoneyFusion fonctionne (avec SSL activé)
- [ ] Les webhooks fonctionnent

### 10. Configuration SSL (HTTPS)

Dans le panneau Hostinger:
1. Activer le SSL/TLS gratuit (Let's Encrypt)
2. Forcer HTTPS en ajoutant dans `.htaccess`:

```apache
# Forcer HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 11. Mise à jour du webhook MoneyFusion

Après le déploiement, mettre à jour les URLs dans `.env`:
```env
APP_URL=https://votre-domaine.com

# Les webhooks seront automatiquement:
# - Callback: https://votre-domaine.com/payment/callback
# - Webhook: https://votre-domaine.com/api/moneyfusion/webhook
```

## Dépannage

### Erreur 500
```bash
# Vérifier les logs
tail -50 ~/laravel/storage/logs/laravel.log

# Vérifier les permissions
chmod -R 775 ~/laravel/storage
chmod -R 775 ~/laravel/bootstrap/cache
```

### Assets non chargés
```bash
# Vérifier que les fichiers build existent
ls -la ~/public_html/build/assets/

# Recompiler si nécessaire (localement puis uploader)
npm run build
```

### Base de données inaccessible
```bash
# Tester la connexion
php artisan tinker
# Puis: DB::connection()->getPdo();
```

### MoneyFusion ne fonctionne pas
```bash
# Vérifier SSL est activé
cat ~/laravel/.env | grep MONEYFUSION_VERIFY_SSL
# Doit être: MONEYFUSION_VERIFY_SSL=true

# Tester l'API
php artisan moneyfusion:test-payment
```

## Structure des dossiers finale

```
/home/u104407086/
├── laravel/                    # Application Laravel complète
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/                # Dossier public Laravel (ne pas utiliser directement)
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
└── public_html/               # Document root du serveur web
    ├── build/                # Assets compilés
    ├── images/
    ├── vendors/
    ├── .htaccess
    └── index.php             # Point d'entrée personnalisé
```

## Commandes utiles

```bash
# Vider le cache
cd ~/laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recompiler le cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Voir les logs en temps réel
tail -f ~/laravel/storage/logs/laravel.log

# Redémarrer l'application
touch ~/laravel/storage/framework/down
php artisan up
```

## Checklist de déploiement

- [ ] Fichiers uploadés sur le serveur
- [ ] Dépendances Composer installées
- [ ] Fichier .env configuré avec les bonnes valeurs
- [ ] Clé d'application générée (APP_KEY)
- [ ] Base de données importée
- [ ] Migrations exécutées
- [ ] public_html configuré correctement
- [ ] Permissions correctes (storage et bootstrap/cache)
- [ ] Cache de configuration/routes/vues généré
- [ ] SSL/HTTPS activé
- [ ] Cron jobs configurés
- [ ] MoneyFusion testé en production
- [ ] Webhooks fonctionnent

## Support

En cas de problème:
1. Vérifier les logs Laravel: `~/laravel/storage/logs/laravel.log`
2. Vérifier les logs du serveur web (via panneau Hostinger)
3. Vérifier les permissions des fichiers
4. Contacter le support Hostinger si problème serveur
