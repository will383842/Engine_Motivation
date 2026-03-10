FROM php:8.2-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    redis \
    icu-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    fcgi

# Install PHP extensions (including Redis)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql intl zip opcache pcntl gd

# OPcache production settings
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cache config/routes/views
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

# Set ownership to www-data
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# PHP-FPM healthcheck script
RUN echo '#!/bin/sh' > /usr/local/bin/php-fpm-healthcheck \
    && echo 'SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 2>/dev/null | grep -q pong' >> /usr/local/bin/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

USER www-data

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

CMD ["php-fpm"]
