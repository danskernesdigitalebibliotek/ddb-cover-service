ARG APP_VERSION="latest"
FROM docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:${APP_VERSION} as APPLICATION

FROM nginx:1.17-alpine
ARG APP_VERSION
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html \
    FILEPATH=infrastructure/docker/nginx

RUN mkdir -p ${APP_PATH}/public

COPY --from=APPLICATION ${APP_PATH}/public ${APP_PATH}/public

WORKDIR ${APP_PATH}

# Copy configuration.
COPY ${FILEPATH}/etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY ${FILEPATH}/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD [ "docker-entrypoint.sh" ]