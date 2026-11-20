
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
    procps \
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

COPY . .
RUN chmod +x docker/production/entrypoint.sh docker/production/healthcheck.sh
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
ENV PORT=10000

# The build context excludes storage and the contents of bootstrap/cache. These
# writable Laravel paths must exist before package discovery compiles views.
RUN mkdir -p storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage/framework bootstrap/cache \
    && chmod -R u+rwX,g+rwX storage/framework bootstrap/cache

RUN php artisan package:discover --ansi

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost [*]:80>/<VirtualHost *:10000>/' \
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
        storage/app/public \
        storage/app/ai-knowledge \
        bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R u+rwX storage bootstrap/cache

EXPOSE 10000

CMD ["apache2-foreground"]
