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
