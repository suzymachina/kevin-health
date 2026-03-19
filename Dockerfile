FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p database storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
RUN touch database/database.sqlite
RUN php artisan migrate --force
RUN php artisan route:cache

# Don't cache config — env vars are injected at runtime by Coolify
# Don't use artisan serve in production — it's single-threaded and hangs on concurrent requests
# Use PHP's built-in server with multiple workers instead

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:clear && PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=0.0.0.0 --port=8080"]
