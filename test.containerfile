FROM composer AS composer
COPY composer.* /app
RUN composer install --ignore-platform-reqs

FROM php:8.1.0RC1
COPY --from=composer /app/vendor /app/vendor
COPY src /app/src
COPY tests /app/tests
ENTRYPOINT ["/usr/local/bin/php", "/app/vendor/bin/phpunit", "/app/tests"]
