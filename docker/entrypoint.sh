#!/bin/sh
set -e

cd /var/www/html

wait_for_db() {
    if [ "${DB_CONNECTION:-mysql}" = "sqlite" ]; then
        return 0
    fi
    echo "Waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    i=0
    while [ "$i" -lt 60 ]; do
        if php artisan db:show >/dev/null 2>&1; then
            echo "Database is ready."
            return 0
        fi
        i=$((i + 1))
        sleep 2
    done
    echo "Database connection timed out." >&2
    return 1
}

fix_permissions() {
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
}

bootstrap_laravel() {
    php artisan package:discover --ansi 2>/dev/null || true
    php artisan storage:link --force 2>/dev/null || true

    if [ "${APP_ENV:-production}" = "production" ]; then
        php artisan config:cache --no-interaction 2>/dev/null || true
        php artisan route:cache --no-interaction 2>/dev/null || true
        php artisan view:cache --no-interaction 2>/dev/null || true
    fi
}

wait_for_db
fix_permissions
bootstrap_laravel

if [ "$1" = "web" ]; then
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        php artisan migrate --force --no-interaction || true
    fi

    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi

exec "$@"
