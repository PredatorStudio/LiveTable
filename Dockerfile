FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        bash \
        git \
        unzip \
        linux-headers \
        libxml2-dev \
        sqlite-dev \
        autoconf \
        build-base \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        dom \
        xml \
        xmlwriter \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del autoconf build-base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
