FROM php:7.2-alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="latest"
ENV APP_PATH=/var/www/html

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0" \
    PHP_OPCACHE_MAX_ACCELERATED_FILES="20000" \
    PHP_OPCACHE_MEMORY_CONSUMPTION="32" \
    PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10" \
    PHP_MEMORY_LIMIT="256M"

# The fcgi packaged is required by the FPM healtcheck script.
RUN apk --update add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        imagemagick \
        imagemagick-libs \
        imagemagick-dev \
        supervisor \
        inotify-tools \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) \
        zip \
        gd \
        opcache \
        mysqli \
        pdo_mysql

RUN apk --update add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis-4.0.1 imagick \
    && docker-php-ext-enable redis imagick \
    && apk del .build-deps

# Copy configuration.
COPY etc/supervisor.d /etc/supervisor.d

# Use default PHP production configuration.
RUN mv ${PHP_INI_DIR}/php.ini-production ${PHP_INI_DIR}/php.ini

# Copy custom PHP configuration.
COPY etc/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/20-opcache.ini
COPY etc/php/conf.d/php.ini /usr/local/etc/php/conf.d/20-php.ini

# Copy application.
COPY --from=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service ${APP_PATH}/ ${APP_PATH}/

ENTRYPOINT ["supervisord"]
CMD ["--nodaemon", "--configuration", "/etc/supervisord.conf"]