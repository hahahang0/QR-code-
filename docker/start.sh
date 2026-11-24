#!/bin/sh
set -eu

: "${PORT:=10000}"
: "${APP_KEY:?Set APP_KEY in the Render environment}"
export PORT

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
