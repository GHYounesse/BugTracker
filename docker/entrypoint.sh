#!/bin/sh
set -e

# Runs on every container start (Render's free tier stops and restarts the
# container often, so this needs to be cheap and safe to repeat).
# --allow-no-migration: don't fail the boot if there's nothing pending.
#
# `depends_on` in docker-compose only waits for the database *container* to
# start, not for Postgres inside it to actually accept connections - so on a
# cold `docker compose up`, migrations can race a database that isn't ready
# yet. Retry for up to a minute instead of failing the whole boot over it.
attempt=0
until php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 12 ]; then
        echo "doctrine:migrations:migrate failed after ${attempt} attempts, giving up" >&2
        exit 1
    fi
    echo "doctrine:migrations:migrate failed (attempt ${attempt}/12), retrying in 5s - database may still be starting up" >&2
    sleep 5
done

# Best-effort: prod mode warms its own cache lazily on the first request if
# this fails or is skipped, so a warmup error here shouldn't stop the boot.
php bin/console cache:warmup --env=prod || true

exec docker-php-entrypoint "$@"
