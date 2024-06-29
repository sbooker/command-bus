ARG PHP_VER=8.2
FROM php:${PHP_VER}-fpm

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
    && rm -rf  /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY php.ini /usr/local/etc/php

COPY --chown=app:app id_rsa /home/app/.ssh/id_rsa
RUN chmod 600 /home/app/.ssh/id_rsa
RUN touch /home/app/.ssh/known_hosts

WORKDIR /opt/app




