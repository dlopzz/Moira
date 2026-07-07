#!/bin/sh
set -e

php artisan config:clear
php artisan config:cache
exec php artisan queue:work --tries=3 --backoff=5 --sleep=3
