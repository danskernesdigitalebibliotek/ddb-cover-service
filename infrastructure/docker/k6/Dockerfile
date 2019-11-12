FROM loadimpact/k6
LABEL maintainer="ITK Dev <itkdev@mkb.aarhus.dk>"

COPY tests.js /

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Remove entry-point from loadimpact image.
ENTRYPOINT [""]

CMD [ "docker-entrypoint.sh" ]