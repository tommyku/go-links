FROM php:7.2-fpm

ENV PROJECT_ROOT /var/www/html

WORKDIR $PROJECT_ROOT

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip unzip curl ssh && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json $PROJECT_ROOT

RUN composer install --no-dev --optimize-autoloader

COPY . $PROJECT_ROOT

EXPOSE 80
