#!/bin/bash
# Обновление CRM на сервере (Timeweb). Запускать в корне проекта на сервере.
# chmod +x deploy.sh && ./deploy.sh

set -e
cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
if [ -f composer.phar ]; then
  COMPOSER="$PHP composer.phar"
fi

echo "==> git pull"
git pull origin main

echo "==> composer install"
$COMPOSER install --optimize-autoloader --no-dev

echo "==> clear cache"
$PHP artisan config:clear
$PHP artisan route:clear
$PHP artisan view:clear
$PHP artisan cache:clear

echo "==> migrate"
$PHP artisan migrate --force

echo "==> cache for production"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo "==> done"
echo "Напоминание: для ИИ в Telegram нужен cron на schedule:run (см. DEPLOY.md) и QUEUE_CONNECTION=database в .env."
