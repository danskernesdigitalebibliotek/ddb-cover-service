#!/bin/sh

set -eux

## Run templates with configuration.
/usr/local/bin/confd --onetime --backend env --confdir /etc/confd

## Start prometheus export
/usr/local/bin/php-fpm_exporter server &

## Start the PHP process.
/usr/local/bin/docker-php-entrypoint php-fpm