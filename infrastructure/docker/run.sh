#!/bin/sh

(cd php7.3-fpm && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.3-fpm:1.1.4 .)
(cd cover-service && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:1.1.4 .)
(cd nginx && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:1.1.4 .)
(cd cover-service-jobs && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs:1.1.4 .)

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.3-fpm:1.1.4
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:1.1.4
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:1.1.4
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs:1.1.4
