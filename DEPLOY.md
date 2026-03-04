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

Если на хостинге старый PHP не в PATH, задайте переменные перед запуском:

```bash
export PHP=/usr/bin/php
export COMPOSER="php composer.phar"   # если используете composer.phar
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
