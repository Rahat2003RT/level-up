FROM php:8.5-fpm-alpine

# Добавили pcre-dev для сборки redis
RUN apk add --no-cache \
    linux-headers libpq-dev zip unzip git curl bash pcre-dev $PHPIZE_DEPS

# Сначала ставим стандартные расширения
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath

# Скачиваем и компилируем нативный PhpRedis
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
