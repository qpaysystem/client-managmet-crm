<?php
/**
 * Проверка подключения к MySQL (MAMP).
 * Запуск: php test_db_connection.php
 */

$socket = '/Applications/MAMP/tmp/mysql/mysql.sock';
$database = 'crm';
$username = 'root';
$password = 'root';

if (!file_exists($socket)) {
    echo "Ошибка: сокет не найден: $socket\n";
    echo "Убедитесь, что MAMP запущен и MySQL работает.\n";
    exit(1);
}

try {
    $dsn = "mysql:unix_socket=$socket;dbname=$database;charset=utf8mb4";
    new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Подключение к MySQL успешно.\n";
    exit(0);
} catch (PDOException $e) {
    echo "Ошибка подключения: " . $e->getMessage() . "\n";
    exit(1);
}
