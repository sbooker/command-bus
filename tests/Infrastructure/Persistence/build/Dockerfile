FROM php:7.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    libzip-dev \
    ssh-client \
    git-core \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) zip opcache pdo pdo_pgsql mysqli pdo_mysql \
    && pecl update-channels \
    && pecl install APCu xdebug \
    && docker-php-ext-enable apcu xdebug \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf  /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && useradd --create-home --uid 1000 --user-group --system app

COPY php.ini /usr/local/etc/php

WORKDIR /opt/app

USER app
