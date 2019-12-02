FROM docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.2-fpm AS APP_BUILDER
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION=develop
ENV APP_PATH=/var/www/html

# Move site into the container.
ADD https://github.com/danskernesdigitalebibliotek/ddb-cover-service/archive/${APP_VERSION}.tar.gz /tmp/app.tar
RUN tar -zxf /tmp/app.tar --strip-components=1 -C ${APP_PATH} \
    && rm /tmp/app.tar

# Add composer in from the official composer image (also alpine).
COPY --from=composer:1.9.1 /usr/bin/composer /usr/bin/composer

WORKDIR ${APP_PATH}

RUN APP_ENV=prod composer install --no-dev -o --no-scripts
RUN APP_ENV=prod composer docker-build

####
## Build main application image.
####
FROM docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.2-fpm
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html

COPY --from=APP_BUILDER ${APP_PATH} ${APP_PATH}
RUN chown -R www-data:www-data ${APP_PATH}/var

# Copy configuration.
COPY etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD [ "docker-entrypoint.sh" ]