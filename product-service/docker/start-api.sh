#!/bin/sh
set -eu

if [ ! -f vendor/autoload.php ]; then
  composer install --prefer-dist --no-interaction
fi

rm -rf var/cache/*

php bin/console doctrine:migrations:migrate -n

exec php -S 0.0.0.0:8000 -t public
