# Развёртывание CRM на хостинге Timeweb

## Быстрый старт (виртуальный хостинг + SFTP)

У вас уже настроен `.vscode/sftp.json` (хост, логин, пароль, `remotePath`). Действия по шагам:

| Шаг | Что сделать |
|-----|-------------|
| 1 | **Локально:** `composer install --optimize-autoloader --no-dev`, сохранить вывод `php artisan key:generate --show` |
| 2 | **Загрузить файлы:** в Cursor `Cmd+Shift+P` → **SFTP: Sync Local -> Remote** (или Upload Active Folder из корня проекта) |
| 3 | **На сервере:** в панели Timeweb указать **корневую папку сайта** = папка с проектом и **Document Root** = `public` (или путь вида `.../public`) |
| 4 | **На сервере:** создать файл `.env` в корне проекта (скопировать с `.env.example`), подставить `APP_KEY`, production-настройки и данные БД из панели Timeweb |
| 5 | **Права:** на сервере (SSH или файловый менеджер) выставить права на запись для `storage` и `bootstrap/cache` (например 775) |
| 6 | **БД:** создать MySQL в панели, выполнить миграции по SSH: `php artisan migrate --force`, при необходимости `php artisan storage:link` |

После этого открыть сайт по домену — должна открыться страница входа в личный кабинет.

---

## Деплой через Git

Подходит для виртуального хостинга Timeweb с SSH: на сервере хранится клон репозитория, обновления — через `git pull`.

### Первоначальная настройка на сервере (один раз)

1. **Подключитесь по SSH** (данные в панели Timeweb: Хостинг → Сайты → SSH).

2. **Клонируйте репозиторий** в нужную директорию (например, домашняя папка или папка сайта):
   ```bash
   cd ~
   git clone https://github.com/qpaysystem/client-managmet-crm.git client-management-crm
   cd client-management-crm
   ```
   Если репозиторий приватный, настройте SSH-ключ или используйте токен в URL: `https://ТОКЕН@github.com/qpaysystem/client-managmet-crm.git`.

3. **Укажите ветку** (если не `main`):
   ```bash
   git checkout main
   ```

4. **Создайте `.env`** в корне проекта (скопируйте с `.env.example`), подставьте `APP_KEY`, `APP_URL`, данные БД из панели Timeweb. Файл `.env` в `.gitignore`, в репозиторий не попадёт.

5. **Document Root** в панели Timeweb укажите на папку `public` этого проекта (например `~/client-management-crm/public` или полный путь).

6. **Права и зависимости:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

После этого сайт должен открываться по домену.

### Обновление (выкладка изменений через Git)

**Локально:** делаете правки, коммит, пуш в репозиторий:
```bash
git add .
git commit -m "Описание изменений"
git push origin main
```

**На сервере** по SSH в каталоге проекта (подставьте свой путь к PHP, если нужно — см. раздел «Проблемы при деплое» ниже):
```bash
cd ~/client-management-crm   # или ваш путь к проекту

git pull origin main

# Composer: если установлен Composer 2 — просто composer; иначе php composer.phar (см. раздел про проблемы)
composer install --optimize-autoloader --no-dev

# Укажите явный путь к PHP, если в PATH старый/битый (например /opt/php56)
/usr/bin/php artisan config:clear
/usr/bin/php artisan route:clear
/usr/bin/php artisan view:clear
/usr/bin/php artisan cache:clear

/usr/bin/php artisan migrate --force

/usr/bin/php artisan config:cache
/usr/bin/php artisan route:cache
/usr/bin/php artisan view:cache
```

Кратко: `git pull` → `composer install --no-dev` → очистка кэша → `migrate --force` → при необходимости снова кэш. Команда **view:cache** — с одной «e» в конце.

### Одной командой на сервере (по желанию)

Можно оформить обновление как скрипт или алиас, например в `~/client-management-crm/deploy.sh` (и не коммитить его в репо, или положить в репо как пример):

```bash
#!/bin/bash
cd "$(dirname "$0")"
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Сделать исполняемым: `chmod +x deploy.sh`. Запуск: `./deploy.sh`.

---

## Внесение изменений на боевой сервер

Когда сайт уже работает, обновления можно выкатывать **через Git** (см. раздел «Деплой через Git» выше) или **через SFTP** (ниже).

### Вариант: через SFTP

#### 1. Локально

- Вносите правки в код, при необходимости коммитите в git.
- Если меняли зависимости в `composer.json`:
  ```bash
  composer install --optimize-autoloader --no-dev
  ```
- Если меняли маршруты, конфиг или шаблоны — перед загрузкой можно обновить кэш (по желанию):
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

#### 2. Загрузка файлов на сервер

- В Cursor: **Cmd+Shift+P** → **SFTP: Sync Local -> Remote** (или Upload Active Folder из корня проекта).
- Файл `.env` не перезаливается — он остаётся тем, что настроен на сервере.

#### 3. На сервере (по SSH или через панель)

Выполнить в корне проекта на боевом сервере:

| Ситуация | Команды |
|----------|---------|
| Обычное обновление кода (без новых миграций) | `php artisan config:clear`<br>`php artisan route:clear`<br>`php artisan view:clear`<br>`php artisan cache:clear` |
| Добавили новые миграции | После загрузки файлов: `php artisan migrate --force` |
| Добавили новые файлы в `storage/app/public` | `php artisan storage:link` (если симлинка ещё нет) |
| Меняли зависимости (composer.json) | На сервере: `composer install --optimize-autoloader --no-dev` (если на хостинге есть Composer). Иначе — загружать обновлённую папку `vendor` с локальной машины после `composer install --no-dev`. |

После очистки кэша приложение само пересоберёт конфиг/маршруты/шаблоны при следующих запросах. Если хотите сразу закэшировать для production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Краткий чек-лист обновления (SFTP)

- [ ] Локально: правки сделаны, при изменении зависимостей — `composer install --no-dev`
- [ ] Загрузка: **SFTP: Sync Local -> Remote**
- [ ] На сервере: очистка кэша (`config:clear`, `route:clear`, `view:clear`, `cache:clear`)
- [ ] Если были миграции — `php artisan migrate --force`

---

## Вариант 1: Timeweb Cloud Apps (рекомендуется)

Подходит, если есть репозиторий на GitHub/GitLab/Bitbucket. Автодеплой, SSL, минимум настроек.

### Шаги

1. **Залейте проект в Git**
   ```bash
   git init
   git add .
   git commit -m "Initial"
   git remote add origin https://github.com/ВАШ_АККАУНТ/client-management-crm.git
   git push -u origin main
   ```

2. **Timeweb Cloud**
   - Зайдите на [timeweb.cloud](https://timeweb.cloud/)
   - Apps → Создать → выберите **Laravel**
   - Подключите GitHub и выберите репозиторий
   - Добавьте переменные окружения (см. ниже)
   - Добавьте в команду сборки: `php artisan migrate --force`
   - Запустите деплой

3. **Переменные окружения** (в настройках приложения)
   - `APP_KEY` — сгенерируйте: `php artisan key:generate --show`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://ваш-домен.ru`
   - `DB_*` — данные MySQL из панели Timeweb
   - `SESSION_DRIVER=database` или `file`

4. **База данных**
   - Создайте MySQL в Timeweb Cloud
   - Импортируйте дамп или выполните миграции при деплое

---

## Вариант 2: Виртуальный хостинг Timeweb

Для обычного shared-хостинга (timeweb.com).

### 1. Подготовка проекта

```bash
# Установка зависимостей без dev
composer install --optimize-autoloader --no-dev

# Генерация ключа (сохраните вывод)
php artisan key:generate --show

# Кэш конфигурации
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Загрузка файлов

#### Вариант A: Через SSH (rsync)

**Данные для подключения** — в панели Timeweb: Хостинг → Сайты → SSH (логин, хост, порт).

На **локальном компьютере** в терминале:

```bash
# Перейдите в папку проекта
cd /Users/evgeny/client-management-crm

# Загрузка на сервер (подставьте свои данные)
rsync -avz --exclude='.env' --exclude='.git' --exclude='node_modules' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' -e "ssh -p ПОРТ" . ЛОГИН@ХОСТ:~/имя_директории_сайта/
```

Пример для Timeweb (порт часто 22 или 222):
```bash
rsync -avz --exclude='.env' --exclude='.git' --exclude='node_modules' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' -e "ssh -p 22" . u1234567@server123.hosting.reg.ru:~/crm.example.ru/
```

Флаг `-a` — сохраняет права, `-v` — прогресс, `-z` — сжатие. Будет запрошен пароль SSH.

#### Вариант B: Через SCP

```bash
cd /Users/evgeny/client-management-crm

# Создайте архив без лишнего
tar --exclude='.env' --exclude='.git' --exclude='node_modules' --exclude='storage/logs' -czf ../crm.tar.gz .

# Загрузите архив
scp -P ПОРТ ../crm.tar.gz ЛОГИН@ХОСТ:~/имя_директории_сайта/
```

Затем по SSH на сервере:
```bash
cd ~/имя_директории_сайта
tar -xzf crm.tar.gz
rm crm.tar.gz
```

#### Вариант C: FTP/SFTP из Cursor

1. **Установите расширение в Cursor**  
   Откройте Extensions (`Cmd+Shift+X`), найдите **SFTP** (автор Natizyskunk) и установите.

2. **Настройте `.vscode/sftp.json`**  
   В проекте уже есть файл `.vscode/sftp.json`. Откройте его и подставьте данные из панели Timeweb (Хостинг → Сайты → FTP):
   - `host` — FTP-сервер (например, `server123.hosting.reg.ru` или из панели)
   - `port` — обычно **21** (FTP) или **22** (SFTP, если включён)
   - `username` — логин FTP
   - `password` — пароль FTP
   - `remotePath` — путь к папке сайта на сервере (например, `/crm.example.ru` или как в панели).

   Если используете SFTP, в `sftp.json` укажите `"protocol": "sftp"` и `"port": 22`.

3. **Загрузка всего проекта**  
   Расширение не даёт загрузить корневую папку по правому клику — только подпапки. Чтобы отправить **весь проект**:
   - **Способ 1:** `Cmd+Shift+P` → введите **Sync** → выберите **SFTP: Sync Local -> Remote**. Синхронизирует весь проект с сервером.
   - **Способ 2:** Откройте любой файл в корне (например, `composer.json`). Затем `Cmd+Shift+P` → **SFTP: Upload Active Folder** — загрузится папка с открытым файлом, т.е. корень проекта.

4. **Важно**  
   - Папка `vendor` загружается (она уже в списке на загрузку). Если на сервере есть SSH, можно не загружать `vendor` (добавьте `"vendor"` в `ignore` в `sftp.json`) и выполнить на сервере `composer install --no-dev`.  
   - Файл `.env` не заливается — создайте его на сервере вручную (см. раздел 4 ниже).
   - После первой загрузки на сервере настройте Document Root на `public/`, права на `storage`/`bootstrap/cache`, выполните миграции.

#### Вариант D: FTP/SFTP (файловый менеджер или другой клиент)

- Подключитесь по FTP/SFTP или используйте файловый менеджер в панели Timeweb
- Залейте все файлы в директорию сайта
- **Не загружайте** папку `vendor` — соберите на сервере через SSH: `composer install --no-dev` (если доступен Composer). Иначе загружайте `vendor` с локальной машины.

### 3. Корневая директория (Document Root)

Laravel должен отдавать содержимое папки `public/`, а не корня проекта.

**Через SSH:**
```bash
# Перейдите в директорию сайта
cd ~/ваш-сайт

# Удалите public_html, если создан автоматически
rm -rf public_html

# Создайте симлинк: public_html → public
ln -s ~/ваш-сайт/public ~/ваш-сайт/public_html
```

Либо в панели Timeweb в настройках сайта укажите корневую папку: `ваш-сайт/public`

### 4. Файл .env на сервере

Создайте `.env` на сервере (скопируйте с `.env.example`):

```env
APP_NAME="CRM"
APP_ENV=production
APP_KEY=base64:ВАШ_КЛЮЧ_ИЗ_КРОМКИ_ВЫШЕ
APP_DEBUG=false
APP_URL=https://ваш-домен.ru

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=имя_базы
DB_USERNAME=пользователь
DB_PASSWORD=пароль

SESSION_DRIVER=file
SESSION_LIFETIME=480
```

Данные БД берутся из панели Timeweb: Хостинг → Базы данных.

### 5. Права доступа

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache  # если 755 не хватает
```

### 6. Миграции и сидеры

Через SSH (если есть доступ):

```bash
cd ~/ваш-сайт
php artisan migrate --force
php artisan db:seed --force  # если нужны начальные данные
```

Если SSH нет — выполните миграции локально, подключившись к удалённой БД, затем экспортируйте дамп и импортируйте через phpMyAdmin.

### 7. Симлинк для storage (изображения, загрузки)

```bash
php artisan storage:link
```

### 8. HTTPS

Для работы через HTTPS добавьте в `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

---

## Проблемы при деплое через Git на Timeweb

### 1. Composer: «requires composer-runtime-api ^2.2 -> no matching package found»

На хостинге стоит **Composer 1.x**, а Laravel 10 требует **Composer 2**. Решение — использовать Composer 2 локально в проекте:

**На сервере** в каталоге проекта:
```bash
cd ~/client-management-crm
curl -sS https://getcomposer.org/installer | php
php composer.phar install --optimize-autoloader --no-dev
```

Дальше при каждом обновлении вместо `composer install ...` выполняйте:
```bash
php composer.phar install --optimize-autoloader --no-dev
```

Если в PATH уже правильный PHP, но он называется не `php`, подставьте его (например `/usr/bin/php composer.phar install ...`).

### 2. PHP: «No such file or directory» или путь /opt/php56/bin/php

В окружении сервера в PATH прописан старый или несуществующий PHP (например `/opt/php56/bin/php`). Все команды `php` и `composer` нужно запускать с **явным путём к рабочему PHP**.

Узнать путь к PHP:
```bash
which php
# или
/usr/bin/php -v
/usr/local/bin/php -v
```

Дальше везде подставляйте этот путь. Пример (если PHP в `/usr/bin/php`):
```bash
/usr/bin/php artisan config:clear
/usr/bin/php artisan migrate --force
/usr/bin/php composer.phar install --no-dev   # если используете composer.phar
```

В скрипте деплоя в начале можно задать переменную:
```bash
export PHP=/usr/bin/php
$PHP artisan config:clear
$PHP artisan migrate --force
```

### 3. Вместе: «php» не найден и Composer требует ^2.2 (типично для Timeweb)

На хостинге по умолчанию `php` ведёт на несуществующий `/opt/php56/bin/php`, а системный Composer — старый. Делайте всё с **явным путём к PHP**.

**Шаг 1.** Найти рабочий PHP (проверьте по очереди):
```bash
/usr/bin/php -v
# или
/usr/local/bin/php -v
# В панели Timeweb: Хостинг → Настройки PHP — там может быть указана версия и путь.
```
Пусть рабочий PHP — это `/usr/bin/php` (подставьте свой, если другой).

**Шаг 2.** Скачать Composer 2 в каталог проекта (используйте тот же путь к PHP):
```bash
cd ~/client-management-crm
curl -sS https://getcomposer.org/installer -o composer-setup.php
/usr/bin/php composer-setup.php --install-dir=. --filename=composer.phar
rm -f composer-setup.php
```

**Шаг 3.** Установить зависимости и обновить проект:
```bash
/usr/bin/php composer.phar install --optimize-autoloader --no-dev
/usr/bin/php artisan config:clear
/usr/bin/php artisan route:clear
/usr/bin/php artisan view:clear
/usr/bin/php artisan cache:clear
/usr/bin/php artisan migrate --force
/usr/bin/php artisan config:cache
/usr/bin/php artisan route:cache
/usr/bin/php artisan view:cache
```

**При следующих обновлениях** после `git pull` достаточно:
```bash
/usr/bin/php composer.phar install --optimize-autoloader --no-dev
/usr/bin/php artisan config:clear && /usr/bin/php artisan route:clear && /usr/bin/php artisan view:clear && /usr/bin/php artisan cache:clear
/usr/bin/php artisan migrate --force
/usr/bin/php artisan config:cache && /usr/bin/php artisan route:cache && /usr/bin/php artisan view:cache
```

Либо один раз задать переменные и запустить скрипт:
```bash
export PHP=/usr/bin/php
export COMPOSER="/usr/bin/php composer.phar"
./deploy.sh
```

### 4. Опечатка в команде

Команда кэша представлений пишется с одной «e»: **view:cache**, не `view:cachee`.

---

## Ошибка 500 после ввода пароля в ЛК

Чаще всего причина — сессии или кэш. Проверьте по шагам:

1. **Лог ошибки**  
   На сервере откройте `storage/logs/laravel.log` (последние строки). Там будет точная причина (например, "Permission denied" или "Route [home] not defined").

2. **Права на запись**  
   Папки, в которые Laravel пишет, должны быть доступны веб-серверу:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```
   Внутри `storage` особенно важны: `storage/framework/sessions`, `storage/framework/cache`, `storage/logs`. Если хостинг даёт одного пользователя для PHP и другого для FTP — уточните в поддержке, как выставить права.

3. **Кэш маршрутов**  
   Если на сервере запускали `php artisan route:cache`, а потом обновили только файлы без повторного кэширования — маршрут `home` мог исчезнуть из кэша. Очистите кэш:
   ```bash
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

4. **HTTPS и куки**  
   Если сайт открывается по `https://`, в `.env` на сервере добавьте:
   ```env
   SESSION_SECURE_COOKIE=true
   APP_URL=https://ваш-домен.ru
   ```
   Иначе кука сессии может не сохраняться и после редиректа «логин» теряется.

5. **Временно включить вывод ошибок**  
   В `.env` на сервере поставить `APP_DEBUG=true`, обновить страницу, увидеть текст ошибки в браузере, затем вернуть `APP_DEBUG=false`.

---

## Чек-лист перед выкладкой

- [ ] `.env` создан на сервере, `APP_DEBUG=false`
- [ ] `APP_KEY` задан
- [ ] База MySQL создана, данные в `.env` верные
- [ ] Document Root указывает на `public/`
- [ ] Папки `storage` и `bootstrap/cache` доступны для записи
- [ ] Миграции выполнены
- [ ] В BotFather для @NskCapital_bot выполнен `/setdomain` с вашим доменом (для Telegram Login)

---

## Полезные ссылки

- [Laravel на виртуальном хостинге Timeweb](https://timeweb.com/ru/docs/virtualnyj-hosting/prilozheniya-i-frejmvorki/laravel)
- [Laravel в Timeweb Cloud Apps](https://timeweb.cloud/tutorials/cloud/kak-razvernut-prilozhenie-na-laravel)
