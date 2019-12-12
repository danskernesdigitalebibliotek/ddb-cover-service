#!/bin/sh

docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/elasticsearch_exporter .

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/elasticsearch_exporter
