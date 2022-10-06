ARG VERSION="latest"
FROM danskernesdigitalebibliotek/cover-service:${VERSION} as APPLICATION

FROM nginxinc/nginx-unprivileged:alpine
ARG APP_VERSION
ARG UID=101
ARG GID=101
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV APP_PATH=/var/www/html

USER root
RUN mkdir -p ${APP_PATH}/public

COPY --from=APPLICATION ${APP_PATH}/public ${APP_PATH}/public

WORKDIR ${APP_PATH}

# Copy configuration.
COPY etc/ /etc/

# Install configuration template handler
ADD https://github.com/kelseyhightower/confd/releases/download/v0.16.0/confd-0.16.0-linux-amd64 /usr/local/bin/confd
RUN chmod +x /usr/local/bin/confd

COPY docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh \
    && chown -R $UID:0 ${APP_PATH} \
    && chmod -R g+w ${APP_PATH}

USER $UID

ENTRYPOINT [ "/docker-entrypoint.sh" ]

CMD ["nginx", "-g", "daemon off;"]
