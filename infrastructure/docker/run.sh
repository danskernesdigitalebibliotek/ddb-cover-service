#!/bin/sh

VERSION=1.1.11

(cd php7.3-fpm && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.3-fpm:${VERSION} .)
(cd cover-service && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:${VERSION} .)
(cd nginx && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:${VERSION} .)
(cd cover-service-jobs && docker build --no-cache --tag=docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs:${VERSION} .)

docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/php7.3-fpm:${VERSION}
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service:${VERSION}
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/nginx:${VERSION}
docker push docker.pkg.github.com/danskernesdigitalebibliotek/ddb-cover-service/cover-service-jobs:${VERSION}
