ARG VERSION="latest"
FROM danskernesdigitalebibliotek/cover-service:${VERSION} as APPLICATION

FROM nginx:1.23-alpine
ARG APP_VERSION
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html

RUN mkdir -p ${APP_PATH}/public

COPY --from=APPLICATION ${APP_PATH}/public ${APP_PATH}/public

WORKDIR ${APP_PATH}

# Copy configuration.
COPY etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD [ "docker-entrypoint.sh" ]
