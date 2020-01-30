#!/bin/sh

(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service --file="infrastructure/docker/cover-service/Dockerfile" .)
(cd ../../ && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx --file="infrastructure/docker/nginx/Dockerfile" .)

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:latest
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:latest
