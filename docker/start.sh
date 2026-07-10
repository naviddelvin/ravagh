#!/bin/bash
set -e
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
    sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi
php artisan migrate:fresh --force --seed
php artisan config:cache || true
php artisan route:cache || true
apache2-foreground
