#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Preparing writable directories..."
mkdir -p var/cache var/log var/sessions/prod public/bundles
chmod -R 775 var || true

if [[ -f .env.local.php ]]; then
    echo "Removing stale .env.local.php (use .env.local instead)..."
    rm -f .env.local.php
fi

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Rebuilding prod cache..."
rm -rf var/cache/prod
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod

echo "Installing bundle assets..."
php bin/console assets:install public --no-interaction --env=prod

echo "Done. Smoke test:"
php bin/console about --env=prod | grep -E "Environment|Log directory" || true
