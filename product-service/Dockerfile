FROM php:8.4-cli-alpine

RUN apk add --no-cache git unzip icu-dev libzip-dev postgresql-dev \
    && docker-php-ext-install intl pdo pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["sh", "-lc", "if [ ! -f vendor/autoload.php ]; then composer install --prefer-dist --no-interaction; fi && php -S 0.0.0.0:8000 -t public"]
