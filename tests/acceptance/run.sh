#!/bin/sh
set -eu

CONTAO="${1:?usage: tests/acceptance/run.sh <contao-version, for example 5.7.*>}"
: "${DATABASE_URL:?set DATABASE_URL to an empty MariaDB or MySQL database}"

SUITE="$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)"
BUNDLE="$(CDPATH='' cd -- "$SUITE/../.." && pwd)"
WORK="$(mktemp -d)"
PROJECT_DIR="$WORK/contao"
ACCEPTANCE_PASSWORD="$(php -r 'echo bin2hex(random_bytes(12));')-Aa1"
COMPOSER_MEMORY_LIMIT=-1
export DATABASE_URL PROJECT_DIR ACCEPTANCE_PASSWORD COMPOSER_MEMORY_LIMIT

mkdir "$WORK/bundle"
tar -C "$BUNDLE" --exclude=./.git --exclude=./vendor --exclude='._*' -cf - . | tar -C "$WORK/bundle" -xf -

composer create-project --no-interaction --no-progress "contao/managed-edition:$CONTAO" "$PROJECT_DIR"
cd "$PROJECT_DIR"
composer config repositories.bundle "{\"type\":\"path\",\"url\":\"$WORK/bundle\",\"options\":{\"versions\":{\"kikmedia/contao-groundingpage-bundle\":\"dev-main\"}}}"
printf 'DATABASE_URL="%s"\nAPP_SECRET=%s\n' "$DATABASE_URL" "$(php -r 'echo bin2hex(random_bytes(16));')" > .env.local
composer require --no-interaction --no-progress "kikmedia/contao-groundingpage-bundle:dev-main"

vendor/bin/contao-console contao:migrate --no-interaction >"$WORK/migrate.log" 2>&1 || { cat "$WORK/migrate.log"; exit 1; }

for user in gp-admin gp-editor; do
    admin=""
    if [ "$user" = gp-admin ]; then admin="--admin"; fi
    vendor/bin/contao-console contao:user:create --username="$user" --name="$user" --email="$user@example.com" \
        --password="$ACCEPTANCE_PASSWORD" --language=de --no-interaction $admin >/dev/null
done

php "$SUITE/seed.php"

php -S 127.0.0.1:8000 -t public public/index.php >"$WORK/server.log" 2>&1 &
SERVER=$!
trap 'kill "$SERVER" 2>/dev/null || true' EXIT

tries=0
until curl --silent --output /dev/null http://127.0.0.1:8000/contao/login; do
    tries=$((tries + 1))
    [ "$tries" -lt 60 ] || { echo "Web server did not start"; cat "$WORK/server.log"; exit 1; }
    sleep 1
done

status=0
php "$SUITE/check.php" || status=$?

if [ "$status" -ne 0 ]; then
    echo "--- application errors ---"
    grep -hE 'CRITICAL|\.ERROR' var/logs/*.log 2>/dev/null | grep -v 'contao:user:create' | cut -c1-400 | tail -20 || true
fi

exit "$status"
