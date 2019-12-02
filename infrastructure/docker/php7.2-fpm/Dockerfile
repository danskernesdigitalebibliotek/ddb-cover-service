FROM php:7.2-fpm-alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="192" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10" \
    PHP_MAX_EXECUTION_TIME="30" \
    PHP_MEMORY_LIMIT="128M" \
    PHP_POST_MAX_SIZE="8M" \
    PHP_UPLOAD_MAX_FILESIZE="2M" \
    PHP_PM_MAX_CHILDREN="40" \
    PHP_PM_START_SERVERS="5" \
    PHP_PM_MIN_SPARE_SERVERS="5" \
    PHP_PM_MAX_SPARE_SERVERS="8"

# The fcgi packaged is required by the FPM healtcheck script.
RUN apk --update add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        imagemagick \
        imagemagick-libs \
        imagemagick-dev \
        libxml2-dev \
        fcgi \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) \
        zip \
        gd \
        opcache \
        mysqli \
        pdo_mysql \
        soap \
        sysvsem

RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis-4.0.1 imagick \
    && docker-php-ext-enable redis imagick \
    && apk del .build-deps

# Added FPM health check script (https://github.com/renatomefi/php-fpm-healthcheck)
ADD https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# Use default PHP production configuration.
RUN mv ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

# Copy custom PHP configuration.
COPY etc/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/20-opcache.ini
COPY etc/php/conf.d/php.ini /usr/local/etc/php/conf.d/20-php.ini

# Custom FPM configuration.
COPY etc/php-fpm.d/fpm.ini /usr/local/etc/php-fpm.d/zz-fpm-docker.conf
