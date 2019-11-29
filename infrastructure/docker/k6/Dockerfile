FROM loadimpact/k6
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

COPY rest.js /
COPY soap.js /

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
