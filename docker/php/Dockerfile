FROM php:8.2-fpm-alpine

RUN apk update && apk add --no-cache \
    libzip-dev zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev linux-headers \
    openssl \
  && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS coreutils \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring zip exif pcntl bcmath sockets \
  && apk del .build-deps \
  && rm -rf /var/cache/apk/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Usuário e pasta
RUN addgroup -g 1000 www && adduser -u 1000 -G www -D www && mkdir -p /var/www && chown -R www:www /var/www
WORKDIR /var/www
USER www

USER root
RUN mkdir -p /var/www/storage/framework/{cache,sessions,views} /var/www/bootstrap/cache \
 && chmod -R 775 /var/www/storage /var/www/bootstrap/cache
USER www

CMD ["php-fpm"]
