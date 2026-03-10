#!/bin/sh
set -e

# Run deferred composer scripts that were skipped during Docker build
# (package:discover + filament:upgrade need DB/Redis available at runtime)
php artisan package:discover --ansi || true
php artisan filament:upgrade || true

# Publish Livewire assets so they can be served as static files by nginx
php artisan livewire:publish --assets || true

# Copy public files to shared volume for Nginx (AFTER publishing assets)
if [ -d /var/www/html/public-shared ]; then
    cp -r /var/www/html/public/* /var/www/html/public-shared/ 2>/dev/null || true
    echo "Public files copied to shared volume"
    ls -la /var/www/html/public-shared/vendor/livewire/ 2>/dev/null || echo "No livewire assets in shared volume"
fi

# Cache config and views (NOT routes — Filament registers routes dynamically)
php artisan config:cache || true
php artisan view:cache || true
php artisan event:cache || true

# Clear any stale route cache (Filament needs dynamic route resolution)
php artisan route:clear || true

exec "$@"
