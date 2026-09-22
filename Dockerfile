# Production-style image, used both by docker-compose (local testing) and by
# any host that runs a plain Dockerfile. FrankenPHP bundles PHP + a webserver
# (Caddy) in one process, which is the officially recommended way to run
# Symfony in a container - no separate php-fpm/nginx config to maintain.
FROM dunglas/frankenphp:1-php8.3 AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# dunglas/frankenphp images ship install-php-extensions (docker-php-extension-installer)
RUN install-php-extensions \
        pdo_pgsql \
        intl \
        opcache \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

# Dependencies in their own layer so `docker build` only re-runs `composer
# install` when composer.json/composer.lock actually change.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

# --no-scripts above skipped composer.json's post-install hooks (cache:clear
# etc.) because they need the full app source, which wasn't copied in yet.
# cache:clear itself still isn't run here: it's deferred to the entrypoint,
# after a real DATABASE_URL is available at runtime, since some warmers touch
# the database and the build environment may not have one to connect to.
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile --env=prod

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
