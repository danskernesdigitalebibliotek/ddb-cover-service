#!/bin/sh

(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service .)
(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx .)

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:latest
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:latest
