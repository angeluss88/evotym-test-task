#!/bin/sh
set -eu

if [ ! -f vendor/autoload.php ]; then
  composer install --prefer-dist --no-interaction
fi

rm -rf var/cache/*

exec php bin/console messenger:consume product_sync --time-limit=3600 --memory-limit=128M -vv
