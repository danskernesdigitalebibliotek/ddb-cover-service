FROM php:7.2-fpm-alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10" \
    PHP_MAX_EXECUTION_TIME="30" \
    PHP_MEMORY_LIMIT="128M" \
    PHP_POST_MAX_SIZE="8M" \
    PHP_UPLOAD_MAX_FILESIZE="2M"

RUN apk --update add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        imagemagick \
        imagemagick-libs \
        imagemagick-dev \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) opcache

RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis-4.0.1 imagick \
    && docker-php-ext-enable redis imagick \
    && apk del .build-deps

# Copy php configuration.
COPY etc/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/20-opcache.ini
COPY etc/php/conf.d/php.ini /usr/local/etc/php/php.ini