#!/bin/sh
set -eu

if [ ! -f vendor/autoload.php ]; then
  composer install --prefer-dist --no-interaction
fi

exec php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M -vv
