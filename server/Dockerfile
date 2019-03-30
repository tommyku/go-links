FROM php:7.3.3-apache

ENV PROJECT_ROOT /var/www/html

WORKDIR $PROJECT_ROOT

RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY composer.json $PROJECT_ROOT

RUN composer install --no-dev --optimize-autoloader

COPY . $PROJECT_ROOT

RUN a2enmod rewrite

EXPOSE 80
