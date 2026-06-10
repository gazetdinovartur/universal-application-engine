FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    && docker-php-ext-install \
    intl \
    opcache \
    pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data var

USER www-data
