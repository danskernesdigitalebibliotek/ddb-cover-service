#!/bin/sh

set -eux

## Run templates with configuration.
/usr/local/bin/confd --onetime --backend env --confdir /etc/confd

## Bump env.local into php for better performance.
composer dump-env prod

## Start prometheus export
/usr/local/bin/php-fpm_exporter server --phpfpm.fix-process-count &

## Warm-up symfony cache (with the current configuration).
/var/www/html/bin/console --env=prod cache:warmup

## Start the PHP process.
/usr/local/bin/docker-php-entrypoint php-fpm
