FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng-dev libzip-dev icu-dev \
    && docker-php-ext-install \
    pdo_mysql zip intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

COPY composer.json composer.lock ./

RUN composer install --prefer-dist --no-autoloader --no-scripts --no-dev

COPY . .

RUN composer dump-autoload --optimize \
    && php bin/console cache:clear --env=prod --no-debug || true \
    && php bin/console cache:warmup --env=prod --no-debug || true \
    && chown -R www-data:www-data var/ \
    && chmod -R 755 /var/www

EXPOSE 9000

CMD ["php-fpm"]
