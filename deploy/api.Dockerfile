# Laravel API: PHP-FPM + nginx (serversideup/php). The same image runs the queue worker and the
# scheduler with another command (see docker-compose.yml). Build from the repo root:
#   docker build -f deploy/api.Dockerfile -t odonto-api .
FROM serversideup/php:8.3-fpm-nginx-alpine-v5.0.2

# intl is not in the base image; bcmath and exif are installed here too (the installer skips
# any extension that is already present).
USER root
RUN install-php-extensions intl bcmath exif
USER www-data

WORKDIR /var/www/html

# Dependencies first, so code changes reuse this layer.
COPY --chown=www-data:www-data backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY --chown=www-data:www-data backend/ ./
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Laravel boots on /up, so the container is healthy only when the app answers.
ENV HEALTHCHECK_PATH=/up \
    SSL_MODE=off \
    PHP_OPCACHE_ENABLE=1 \
    PHP_UPLOAD_MAX_FILE_SIZE=12M \
    PHP_POST_MAX_SIZE=12M
