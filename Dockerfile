
# Stage 1: Provide Composer without using its default PHP runtime.
FROM composer:2 AS composer-bin

# Stage 2: Install PHP dependencies with the same PHP version and required
# extensions used by the production image. Composer validates these while
# resolving/installing the locked packages.
FROM php:8.5-cli AS vendor

WORKDIR /app

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        gd \
        exif \
        pcntl \
        mbstring \
        xml \
        curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./


RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Stage 3: Build frontend assets
FROM node:24-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci --ignore-scripts \
    && mkdir -p /opt/playwright \
    && PLAYWRIGHT_BROWSERS_PATH=/opt/playwright npx playwright install chromium

COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN npm run build

# Stage 4: Laravel application
FROM php:8.5-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libmagickwand-dev \
    imagemagick \
    libreoffice-writer \
    ghostscript \
    fonts-liberation \
    nodejs \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        gd \
        exif \
        pcntl \
        mbstring \
        xml \
        curl \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Render Secret Files are mounted with access for group 1000. Apache serves
# requests as www-data, so grant that runtime user access without copying the
# secret into the image.
RUN (getent group 1000 >/dev/null || groupadd --gid 1000 render-secrets) \
    && usermod -a -G 1000 www-data

COPY . .
RUN rm -f bootstrap/cache/packages.php \
    bootstrap/cache/services.php \
    bootstrap/cache/config.php \
    bootstrap/cache/routes-v7.php
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/node_modules ./node_modules
COPY --from=frontend /opt/playwright /opt/playwright

ENV APP_ENV=production
ENV APP_DEBUG=false

RUN php artisan package:discover --ansi

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    && mkdir -p storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/private/temp-storage \
        storage/app/private/temp-downloads \
        storage/app/private/temp-previews \
        storage/app/private/document-previews \
        storage/app/private/document-thumbnails \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u+rwX storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "if [ \"${LEXTRACK_DB_DIAGNOSTICS:-false}\" = \"true\" ]; then su -s /bin/sh www-data -c 'php /var/www/html/docker/render-db-diagnostic.php' || true; fi; sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
