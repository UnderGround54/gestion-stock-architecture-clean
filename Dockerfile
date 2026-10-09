# --- Stage 1 : dépendances ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock symfony.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# --- Stage 2 : runtime ---
FROM php:8.2-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs \
 && apk add --no-cache --virtual .build icu-dev $PHPIZE_DEPS \
 && docker-php-ext-install intl pdo_mysql opcache \
 && apk del .build

WORKDIR /var/www/html
COPY --from=vendor /app .
RUN APP_ENV=prod php bin/console cache:warmup \
 && chown -R www-data:www-data var

USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# --- Stage 3 : serveur HTTP ---
FROM nginx:alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=runtime /var/www/html/public /var/www/html/public
