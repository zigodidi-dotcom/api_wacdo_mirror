#!/usr/bin/env sh
set -eu

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

if [ "${CREATE_ADMIN:-0}" = "1" ]; then
      echo "[createadmin] Loads..."
  php bin/console app:create-admin "${ADMIN_EMAIL:?missing}" "${ADMIN_PASSWORD:?missing}" "${ADMIN_PRENOM:-Admin}" "${ADMIN_NOM:-User}"
else
    echo "[createadmin] Skipping loads"
fi

echo "Starting app on port ${PORT:-8080}..."
exec php -S 0.0.0.0:${PORT:-8080} -t public
