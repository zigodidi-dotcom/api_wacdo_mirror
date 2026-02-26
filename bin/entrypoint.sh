#!/usr/bin/env sh
set -eu

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Starting app on port ${PORT:-8080}..."
exec php -S 0.0.0.0:${PORT:-8080} -t public
