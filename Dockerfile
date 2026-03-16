FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng-dev libzip-dev icu-dev openssl \
    && docker-php-ext-install \
    pdo_mysql zip intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony

COPY composer.json composer.lock ./

RUN composer install \
    --prefer-dist \
    --no-autoloader \
    --no-scripts \
    --no-interaction

COPY . .

RUN composer dump-autoload --optimize --no-dev

RUN mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ]; then \
        openssl genpkey -algorithm RSA \
            -out config/jwt/private.pem \
            -pkeyopt rsa_keygen_bits:4096 \
            -pass pass:${JWT_PASSPHRASE:-changeme}; \
        openssl rsa \
            -pubout \
            -in config/jwt/private.pem \
            -out config/jwt/public.pem \
            -passin pass:${JWT_PASSPHRASE:-changeme}; \
    fi \
    && chmod 600 config/jwt/private.pem \
    && chmod 644 config/jwt/public.pem

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ \
    && chmod -R 775 var/

EXPOSE 9000

CMD ["sh", "-c", \
    "php bin/console cache:clear --env=${APP_ENV:-prod} --no-debug && \
     php bin/console cache:warmup --env=${APP_ENV:-prod} --no-debug && \
     php-fpm"]