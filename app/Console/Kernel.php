<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Очередь ИИ для Telegram: без cron/worker задачи остаются в таблице jobs.
        $schedule->command('queue:work database --stop-when-empty --max-time=55 --timeout=240')
            ->everyMinute()
            ->withoutOverlapping(5);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
