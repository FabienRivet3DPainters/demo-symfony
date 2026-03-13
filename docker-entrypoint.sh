#!/bin/sh
set -e

echo "Attente MySQL..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  sleep 2
done

echo "Migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Cache..."
php bin/console cache:clear --env=prod

exec "$@"