#!/usr/bin/env bash
set -euo pipefail

# Seed the dedicated writable application volume from the read-only checkout.
# tar overlays files so this is safe to run again without deleting dependencies
# that may already have been installed in the isolated volume.
mkdir -p /var/www/html /home/sail/.composer

tar -C /opt/sam-source \
    --exclude='./.git' \
    --exclude='./.env' \
    --exclude='./.env.*' \
    --exclude='./vendor' \
    --exclude='./node_modules' \
    --exclude='./storage' \
    --exclude='./bootstrap/cache' \
    -cf - . | tar -C /var/www/html -xf -

mkdir -p \
    /var/www/html/vendor \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/cache/phpunit \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R 1337:1337 \
    /var/www/html/vendor \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /home/sail/.composer
