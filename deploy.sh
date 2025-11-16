#!/bin/bash

# Script de déploiement automatisé pour Hostinger
# Usage: ./deploy.sh

set -e

echo "=========================================="
echo "Déploiement Glowing Cosmetics - Hostinger"
echo "=========================================="
echo ""

# Configuration
SSH_HOST="82.25.113.207"
SSH_PORT="65002"
SSH_USER="u104407086"
REMOTE_PATH="~/laravel"
PUBLIC_PATH="~/public_html"

echo "1. Préparation du projet..."
echo "-------------------------------------------"

# Compiler les assets
echo "Compilation des assets..."
npm run build

# Optimiser Composer
echo "Optimisation de Composer..."
composer install --optimize-autoloader --no-dev

# Nettoyer le cache local
echo "Nettoyage du cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "2. Création de l'archive..."
echo "-------------------------------------------"

# Créer le dossier de build
mkdir -p build

# Créer l'archive (exclure les fichiers inutiles)
echo "Création de l'archive ZIP..."
zip -r build/cosmetics-app.zip . \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*.env*" \
    -x "*storage/logs/*" \
    -x "*storage/framework/sessions/*" \
    -x "*storage/framework/views/*" \
    -x "*storage/framework/cache/*" \
    -x "*build/*" \
    -x "*tests/*" \
    -x "*.md" \
    -x "*deploy.sh"

echo "Archive créée: build/cosmetics-app.zip"

echo ""
echo "3. Upload vers le serveur..."
echo "-------------------------------------------"

# Upload de l'archive
echo "Upload de l'archive (cela peut prendre quelques minutes)..."
scp -P $SSH_PORT build/cosmetics-app.zip $SSH_USER@$SSH_HOST:~/

echo ""
echo "4. Configuration sur le serveur..."
echo "-------------------------------------------"

# Exécuter les commandes sur le serveur
ssh -p $SSH_PORT $SSH_USER@$SSH_HOST << 'ENDSSH'
echo "Décompression de l'archive..."
cd ~/laravel
unzip -o ~/cosmetics-app.zip
rm ~/cosmetics-app.zip

echo "Installation des dépendances Composer..."
composer install --optimize-autoloader --no-dev

echo "Configuration des permissions..."
chmod -R 755 ~/laravel
chmod -R 775 ~/laravel/storage
chmod -R 775 ~/laravel/bootstrap/cache

echo "Optimisation de Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload -o

echo "Déploiement terminé sur le serveur!"
ENDSSH

echo ""
echo "=========================================="
echo "✓ Déploiement terminé avec succès!"
echo "=========================================="
echo ""
echo "Prochaines étapes:"
echo "1. Vérifier que l'application fonctionne sur votre domaine"
echo "2. Tester MoneyFusion en production"
echo "3. Vérifier les logs: ssh -p $SSH_PORT $SSH_USER@$SSH_HOST 'tail -f ~/laravel/storage/logs/laravel.log'"
echo ""
