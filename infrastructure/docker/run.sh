#!/bin/sh

(cd ../../ && docker build --no-cache --tag=danskernesdigitalebibliotek/cover-service:1.2.11 --file="infrastructure/docker/cover-service/Dockerfile" .)
(cd ../../ && docker build --no-cache --tag=danskernesdigitalebibliotek/cover-service-nginx:1.2.11 --file="infrastructure/docker/nginx/Dockerfile" .)

docker push danskernesdigitalebibliotek/cover-service:1.2.11
docker push danskernesdigitalebibliotek/cover-service-nginx:1.2.11
