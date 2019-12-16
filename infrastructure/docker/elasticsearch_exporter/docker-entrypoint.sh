#!/bin/sh

set -eux

/bin/elasticsearch_exporter  --es.uri=http://${ES_HOST}:${ES_PORT}