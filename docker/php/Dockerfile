FROM php:8.3-fpm-alpine
RUN apk add --no-cache bash git unzip icu-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
 && docker-php-ext-configure intl \
 && docker-php-ext-install pdo pdo_mysql intl zip gd
# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
