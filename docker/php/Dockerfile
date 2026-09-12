FROM php:8.2-fpm-alpine

# Установка зависимостей
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    autoconf \
    g++ \
    make \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN ln -s /usr/bin/composer /usr/local/bin/composer 2>/dev/null || true

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
