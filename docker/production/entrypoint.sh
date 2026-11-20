#!/bin/sh
set -eu

read_secret() {
    secret_path="$1"
    if [ ! -r "$secret_path" ]; then
        echo "Missing or unreadable secret: $secret_path" >&2
        exit 1
    fi
    cat "$secret_path"
}

export APP_KEY="$(read_secret /run/secrets/app_key)"
export DB_PASSWORD="$(read_secret /run/secrets/db_password)"

# These directories and their initial ownership are created in the image.
# Docker copies that tree into a new named volume. Existing volumes are never
# recursively changed here: a deployment/provisioning operation must repair
# their ownership deliberately, while this check prevents a broken container
# from reporting healthy or silently creating root-owned application files.
for directory in \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/storage/app/private \
    /var/www/html/storage/app/private/temp-storage \
    /var/www/html/storage/app/private/temp-downloads \
    /var/www/html/storage/app/private/temp-previews \
    /var/www/html/storage/app/private/document-previews \
    /var/www/html/storage/app/private/document-thumbnails \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/ai-knowledge \
    /var/www/html/bootstrap/cache; do
    if [ ! -d "$directory" ]; then
        # Only create missing directories. Existing volume contents are never
        # recursively chowned or chmodded during a restart.
        if ! install -d -o www-data -g www-data -m 0770 "$directory"; then
            echo "Unable to create required directory: $directory" >&2
            exit 1
        fi
    fi
    if ! su -s /bin/sh www-data -c "test -w '$directory'"; then
        echo "Directory is not writable by www-data: $directory" >&2
        echo "Repair this volume during controlled provisioning; startup will not chown existing files." >&2
        exit 1
    fi
done

case "${LEXTRACK_ROLE}" in
    web)
        if [ ! -L /var/www/html/public/storage ] \
            || [ "$(readlink /var/www/html/public/storage)" != "/var/www/html/storage/app/public" ]; then
            echo "public/storage is not the expected symbolic link" >&2
            exit 1
        fi
        exec apache2-foreground
        ;;
    queue|scheduler)
        if [ "$#" -eq 0 ]; then
            echo "No Artisan command supplied for ${LEXTRACK_ROLE}" >&2
            exit 1
        fi
        # End su option parsing before the username. The explicit shell name
        # makes the first Artisan argument $1, so command options such as
        # --force are forwarded to Artisan instead of being parsed by su.
        exec su -s /bin/sh -c 'exec php artisan "$@"' -- www-data lextrack "$@"
        ;;
    *)
        echo "Unsupported LEXTRACK_ROLE: ${LEXTRACK_ROLE:-unset}" >&2
        exit 1
        ;;
esac
