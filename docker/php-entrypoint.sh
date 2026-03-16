#!/bin/sh
set -e

cd /var/www/symfony

mkdir -p var config/jwt
chown -R www-data:www-data var config/jwt

if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
  echo "[entrypoint] JWT keys not found, generating..."
  php bin/console lexik:jwt:generate-keypair --skip-if-exists --no-interaction || true
fi

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
  echo "[entrypoint] Running database migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
fi

exec php-fpm
