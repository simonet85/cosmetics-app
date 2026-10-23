#!/bin/sh
set -e

cd /var/www/html

[ -f .env ] || cp .env.example .env

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

# Only the `app` service prepares the database (compose waits for the db healthcheck).
if [ "$1" = "php-fpm" ]; then
    php artisan migrate --force
    php artisan storage:link 2>/dev/null || true
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
