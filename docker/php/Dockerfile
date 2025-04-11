FROM composer:2 as composer_stage

RUN rm -rf /var/www && mkdir -p /var/www/html
WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --ignore-platform-reqs --prefer-dist --no-scripts --no-progress --no-interaction --no-autoloader

RUN composer dump-autoload --optimize --apcu

FROM php:8.3-fpm

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql

COPY --from=composer_stage /var/www/html /var/www/html

WORKDIR /var/www

COPY src html/src/
COPY .env html/.env
COPY config html/config
COPY bin html/bin
COPY scripts html/scripts

WORKDIR /var/www/html

EXPOSE 9000