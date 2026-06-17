FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    linux-headers libpq-dev zip unzip git curl bash pcre-dev $PHPIZE_DEPS \

RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-interaction --no-plugins --no-scripts --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data
