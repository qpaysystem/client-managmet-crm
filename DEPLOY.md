# Деплой CRM — кратко

Подробности и хостинг Timeweb: [DEPLOYMENT_TIMEWEB.md](DEPLOYMENT_TIMEWEB.md).

## Сейчас выкатить (код уже в Git)

Код запушен в `origin main`. Дальше — один из вариантов.

### Вариант 1: На сервере через Git (рекомендуется)

По SSH зайти в каталог проекта и выполнить:

```bash
cd ~/client-management-crm   # или ваш путь

git pull origin main
composer install --optimize-autoloader --no-dev   # или: php composer.phar install --no-dev
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Либо один раз сделать скрипт исполняемым и запускать его:

```bash
chmod +x deploy.sh
./deploy.sh
```

**Если на сервере «php» не найден и Composer выдаёт «composer-runtime-api ^2.2»** — на Timeweb иногда `php` указывает не туда. Используйте явный путь к PHP (часто `/usr/bin/php`) или алиас вроде `php81`. Сначала установите Composer 2 в проект:

```bash
cd ~/client-management-crm
curl -sS https://getcomposer.org/installer -o composer-setup.php
/usr/bin/php composer-setup.php --install-dir=. --filename=composer.phar
rm -f composer-setup.php
```

Дальше все команды — с этим PHP:
```bash
/usr/bin/php composer.phar install --optimize-autoloader --no-dev
/usr/bin/php artisan config:clear && /usr/bin/php artisan route:clear && /usr/bin/php artisan view:clear && /usr/bin/php artisan cache:clear
/usr/bin/php artisan migrate --force
/usr/bin/php artisan config:cache && /usr/bin/php artisan route:cache && /usr/bin/php artisan view:cache
```

Подробнее: [DEPLOYMENT_TIMEWEB.md](DEPLOYMENT_TIMEWEB.md) — раздел «Проблемы при деплое», пункт 3.

Если PHP у вас в другом месте (узнать: `which php` или `/usr/local/bin/php -v`), подставьте его вместо `/usr/bin/php`. Для deploy.sh:

```bash
export PHP="$(command -v php)"   # или: export PHP=/usr/bin/php / export PHP=php81
export COMPOSER="/usr/bin/php composer.phar"
./deploy.sh
```

### Вариант 2: Через SFTP

1. **Локально** (у себя в проекте):
   ```bash
   composer install --optimize-autoloader --no-dev
   ```
2. В Cursor: **Cmd+Shift+P** → **SFTP: Sync Local -> Remote** (загрузить проект на сервер).
3. **На сервере** по SSH в корне проекта:
   ```bash
   php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

---

- Document Root на хостинге должен указывать на папку **public** проекта.
- Файл **.env** на сервере не трогать при обновлении (создаётся вручную, в репозиторий не попадает).
