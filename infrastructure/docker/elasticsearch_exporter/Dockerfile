FROM justwatch/elasticsearch_exporter:1.1.0
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

ENV ES_HOST='127.0.0.1' \
    ES_PORT='9200'

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]