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
    && chown -R www-data:www-data var/ \
    && chmod -R 755 /var/www \
    && chmod +x /var/www/symfony/docker/php-entrypoint.sh

EXPOSE 9000

CMD ["/var/www/symfony/docker/php-entrypoint.sh"]
