FROM composer AS composer
COPY composer.* /app
RUN composer install --ignore-platform-reqs

FROM php:8.1.0RC2
COPY --from=composer /app/vendor /app/vendor
COPY src /app/src
COPY tests /app/tests
