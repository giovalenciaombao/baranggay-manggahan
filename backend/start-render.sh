#!/bin/sh
set -eu

php artisan migrate --force

if [ -n "${DEMO_ADMIN_EMAIL:-}" ]; then
    php artisan db:seed --class=DemoAdminSeeder --force
fi

exec apache2-foreground
