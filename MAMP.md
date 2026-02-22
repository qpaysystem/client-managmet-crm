# Подключение к MySQL в MAMP

Если при `php artisan migrate` появляется **Connection refused**, настройте `.env` под MAMP.

## 1. Запустите MAMP

Откройте MAMP → **Start Servers**. Должны быть зелёные индикаторы у Apache и MySQL.

## 2. Вариант A: подключение по порту

В MAMP: **Preferences → Ports**. Посмотрите порт MySQL (часто **8889**).

В файле **.env** замените блок БД на:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=root
```

Сохраните файл и выполните:

```bash
php artisan config:clear
php artisan migrate
```

## 3. Вариант B: подключение через сокет (если порт не подошёл)

В **.env** укажите:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=root
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock
```

Затем:

```bash
php artisan config:clear
php artisan migrate
```

## 4. Создайте базу вручную

Если базы `crm` ещё нет: откройте в браузере **http://localhost:8888/phpMyAdmin/** (или страницу MAMP → Tools → phpMyAdmin), создайте базу **crm** (кодировка utf8mb4_unicode_ci).
