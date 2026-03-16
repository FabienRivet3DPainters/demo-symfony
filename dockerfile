FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng-dev libzip-dev icu-dev \
    && docker-php-ext-install \
    pdo_mysql zip intl opcache

# Configuration PHP pour production
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=10M" >> /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

COPY composer.json composer.lock ./

RUN composer install --prefer-dist --no-autoloader --no-scripts --no-dev

COPY . .

RUN composer dump-autoload --optimize \
    && php bin/console cache:clear --env=prod --no-debug || true \
    && php bin/console doctrine:migrations:migrate --no-interaction --env=prod || true \
    && php bin/console cache:warmup --env=prod --no-debug || true \
    && chown -R www-data:www-data var/ \
    && chmod -R 755 /var/www

EXPOSE 9000

CMD ["php-fpm"]