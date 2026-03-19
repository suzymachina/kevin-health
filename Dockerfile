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
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
RUN php artisan route:cache

EXPOSE 8080

CMD ["sh", "-c", "mkdir -p database && touch database/database.sqlite && php artisan config:clear && php artisan migrate --force && PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=0.0.0.0 --port=8080"]
