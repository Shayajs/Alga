# PHP-FPM prêt pour Laravel (PDO MySQL, mbstring, zip, intl, opcache, gd, …)
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libzip-dev \
    icu-dev \
    icu-data-full \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        opcache \
        gd \
        exif \
        pcntl \
        dom \
        xml

RUN apk del --purge \
    libzip-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    && apk add --no-cache \
        libzip \
        icu-libs \
        icu-data-full \
        libpng \
        libjpeg-turbo \
        freetype \
        oniguruma \
        libxml2

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
