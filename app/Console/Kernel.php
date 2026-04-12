<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Очередь ИИ для Telegram: без cron/worker задачи остаются в таблице jobs.
        // timeout/max-time ≥ джобы вебхука с OpenAI (до 300 с)
        $schedule->command('queue:work database --stop-when-empty --max-time=320 --timeout=300')
            ->everyMinute()
            ->withoutOverlapping(5);
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
