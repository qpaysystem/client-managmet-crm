<?php

/**
 * Вход для планировщика хостинга (Timeweb и др.), где в Cron указывают только путь к PHP-файлу.
 * В панели: интерпретатор PHP 8.2, путь к этому файлу, расписание «каждую минуту».
 *
 * Эквивалент: php artisan schedule:run
 */
declare(strict_types=1);

$base = __DIR__;

require $base.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once $base.'/bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$status = $kernel->call('schedule:run');

exit(is_int($status) ? $status : 0);
