FROM composer AS composer

COPY . /app

RUN composer install \
  --optimize-autoloader \
  --no-interaction \
  --no-progress

FROM trafex/php-nginx
COPY --chown=nginx --from=composer /app /var/www/html
COPY --chown=nginx phlo/conf.d/default.conf /etc/nginx/conf.d/default.conf
