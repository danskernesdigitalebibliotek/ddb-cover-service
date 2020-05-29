#!/bin/sh

APP_VERSION=develop
VERSION=latest

docker build --no-cache --build-arg APP_VERSION=${APP_VERSION} --tag=danskernesdigitalebibliotek/cover-service:${VERSION} --file="cover-service/Dockerfile" cover-service
docker build --no-cache --build-arg VERSION=${VERSION} --tag=danskernesdigitalebibliotek/cover-service-nginx:${VERSION} --file="nginx/Dockerfile" nginx
docker build --no-cache --tag=danskernesdigitalebibliotek/cover-service-landing:${VERSION} --file="cover-service-landing/Dockerfile" cover-service-landing

docker push danskernesdigitalebibliotek/cover-service:${VERSION}
docker push danskernesdigitalebibliotek/cover-service-nginx:${VERSION}
docker push danskernesdigitalebibliotek/cover-service-landing:${VERSION}
