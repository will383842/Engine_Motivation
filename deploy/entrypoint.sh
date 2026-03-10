#!/bin/sh
set -e

# Copy public files to shared volume for Nginx
if [ -d /var/www/html/public-shared ]; then
    cp -r /var/www/html/public/* /var/www/html/public-shared/ 2>/dev/null || true
fi

# Run deferred composer scripts that were skipped during Docker build
# (package:discover + filament:upgrade need DB/Redis available at runtime)
php artisan package:discover --ansi 2>/dev/null || true
php artisan filament:upgrade 2>/dev/null || true

# Cache config on startup (uses runtime env vars from docker-compose env_file)
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true
php artisan event:cache 2>/dev/null || true

exec "$@"
