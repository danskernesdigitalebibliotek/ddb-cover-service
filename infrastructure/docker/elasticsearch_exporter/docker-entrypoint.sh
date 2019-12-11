#!/bin/sh

set -eux

/bin/elasticsearch_exporter  --es.uri=https://${ES_USERNAME}:${ES_PASSWORD}@${ES_HOST}:${ES_PORT} --es.ssl-skip-verify