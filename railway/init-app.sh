#!/bin/sh
php artisan migrate --force
php artisan db:seed --class=PlatformSeeder --force
