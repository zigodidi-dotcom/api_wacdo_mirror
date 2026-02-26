#!/usr/bin/env sh
set -eu

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ "${LOAD_FIXTURES:-0}" = "1" ]; then
  echo "[entrypoint] Loading fixtures..."
  php bin/console doctrine:fixtures:load --no-interaction
else
  echo "[entrypoint] Skipping fixtures (set LOAD_FIXTURES=1 to enable)"
fi


echo "Starting app on port ${PORT:-8080}..."
exec php -S 0.0.0.0:${PORT:-8080} -t public
