FROM nginx:alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY . /var/www/symfony

RUN chown -R nginx:nginx /var/www/symfony \
    && find /var/www/symfony -type d -exec chmod 755 {} \; \
    && find /var/www/symfony -type f -exec chmod 644 {} \;