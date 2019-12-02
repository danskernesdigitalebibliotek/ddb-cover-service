#!/bin/sh

(cd php7.2-fpm && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.2-fpm .)
(cd cover-service && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service .)
(cd nginx && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx .)
(cd cover-service-jobs && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs .)

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.2-fpm
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs
