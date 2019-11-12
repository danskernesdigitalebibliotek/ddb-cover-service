FROM nginx:1.17-alpine
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ARG APP_VERSION="latest"
ENV APP_PATH=/var/www/html

RUN mkdir -p ${APP_PATH}/public

COPY --from=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:${APP_VERSION} ${APP_PATH}/public ${APP_PATH}/public

WORKDIR ${APP_PATH}

# Copy configuration.
COPY etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD [ "docker-entrypoint.sh" ]