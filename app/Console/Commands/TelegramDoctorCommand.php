<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\TelegramGroupMessage;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TelegramDoctorCommand extends Command
{
    protected $signature = 'telegram:doctor';

    protected $description = 'Проверка Telegram: настройки CRM, очередь, вебхук (getWebhookInfo), последние сообщения в БД';

    public function handle(): int
    {
        $this->line('=== CRM / .env ===');
        $this->line('APP_URL: '.config('app.url'));
        $this->line('QUEUE_CONNECTION: '.config('queue.default'));
        if (config('queue.default') === 'sync') {
            $this->warn('Рекомендуется QUEUE_CONNECTION=database в .env, затем php artisan config:clear — ответы ИИ обрабатывает очередь + cron schedule:run.');
        }
        $this->line('Ожидаемый URL вебхука: '.rtrim((string) config('app.url'), '/').'/telegram/webhook');

        $this->newLine();
        $this->line('=== Очередь ===');
        if (! Schema::hasTable('jobs')) {
            $this->warn('Таблицы jobs нет — выполните: php artisan migrate');
        } else {
            $this->line('jobs в очереди: '.(int) DB::table('jobs')->count());
        }
        if (Schema::hasTable('failed_jobs')) {
            $this->line('failed_jobs: '.(int) DB::table('failed_jobs')->count());
        }

        $this->newLine();
        $this->line('=== Настройки (settings), без секретов ===');
        $chatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        $this->line('telegram_chat_id: '.($chatId !== '' ? $chatId : '(пусто)'));
        $this->line('Если новые сообщения не сохраняются: id чата должен совпадать с текущим (после «супергруппы» id часто -100…; возьмите у @RawDataBot / @userinfobot в нужной группе).');
        $token = (string) Setting::get('telegram_bot_token', '');
        $this->line('telegram_bot_token: '.($token !== '' ? 'задан ('.strlen($token).' симв.)' : '(пусто)'));
        $secret = (string) Setting::get('telegram_webhook_secret', '');
        $this->line('telegram_webhook_secret: '.($secret !== '' ? 'задан (проверяйте заголовок у Telegram)' : '(пусто — секрет не требуется)'));
        $this->line('telegram_group_ai_all: '.(string) Setting::get('telegram_group_ai_all', '1'));
        $apiKey = (string) Setting::get('ai_api_key', Setting::get('openai_api_key', ''));
        $this->line('ai_api_key / openai: '.($apiKey !== '' ? 'задан' : '(пусто — ИИ в Telegram не запустится)'));

        $this->newLine();
        $this->line('=== Сообщения в telegram_group_messages ===');
        if ($chatId === '') {
            $this->warn('Chat ID не задан — дубль переписки и фильтр вебхука не сработают.');
        } else {
            $total = TelegramGroupMessage::query()->where('chat_id', $chatId)->count();
            $out = TelegramGroupMessage::query()
                ->where('chat_id', $chatId)
                ->whereIn('from_first_name', ['ИИ-агент', 'Бот (ошибка)', 'Справка'])
                ->count();
            $this->line("chat_id={$chatId}: всего {$total}, из них ответов бота в БД: {$out}");
            $last = TelegramGroupMessage::query()->where('chat_id', $chatId)->orderByDesc('id')->first();
            if ($last) {
                $this->line('Последняя запись id='.$last->id.' message_date='.($last->message_date?->format('Y-m-d H:i:s') ?? 'null'));
            }
        }

        $this->newLine();
        $this->line('=== Telegram API: getWebhookInfo ===');
        if ($token === '') {
            $this->error('Нет токена бота — проверьте настройки CRM.');
            return self::FAILURE;
        }

        $resp = Http::timeout(15)->get('https://api.telegram.org/bot'.$token.'/getWebhookInfo');
        if (! $resp->successful()) {
            $this->error('HTTP '.$resp->status().' при запросе к Telegram');
            return self::FAILURE;
        }
        $j = $resp->json();
        if (empty($j['ok']) || ! is_array($j['result'] ?? null)) {
            $this->error('Ответ Telegram: '.substr($resp->body(), 0, 500));

            return self::FAILURE;
        }
        $r = $j['result'];
        $expectedPath = '/telegram/webhook';
        $url = (string) ($r['url'] ?? '');

        $this->line('url: '.($url !== '' ? $url : '(пусто — вебхук не установлен!)'));
        $this->line('pending_update_count: '.($r['pending_update_count'] ?? 0));
        if (! empty($r['last_error_message'])) {
            $this->error('last_error_message: '.$r['last_error_message']);
            if (! empty($r['last_error_date'])) {
                $this->error('last_error_date: '.date('Y-m-d H:i:s', (int) $r['last_error_date']));
            }
            $this->newLine();
            $this->error('Telegram не может достучаться до вашего URL (часто: таймаут, закрыт 443, не тот домен, SSL).');
            $checkUrl = $url !== '' ? $url : rtrim((string) config('app.url'), '/').$expectedPath;
            $this->line('Проверьте с внешней сети: curl -I '.escapeshellarg($checkUrl));
            $this->line('APP_URL в .env должен совпадать с публичным HTTPS сайта, по которому открывается CRM.');
        } else {
            $this->line('last_error_message: (нет)');
        }

        if ($url === '') {
            $this->newLine();
            $this->warn('Вебхук не используется — ок для схемы `php artisan telegram:poll` (long polling). Чтобы снова слать апдейты на сайт, установите вебхук:');
            $this->line('curl "https://api.telegram.org/bot<TOKEN>/setWebhook?url='.urlencode(rtrim((string) config('app.url'), '/').$expectedPath).'"');
        } elseif (str_contains($url, $expectedPath) === false) {
            $this->warn('URL вебхука не содержит '.$expectedPath.' — проверьте маршрут в Laravel.');
        }

        $this->newLine();
        $this->line('Если jobs > 0 и не уменьшается: cron должен вызывать `php artisan schedule:run` каждую минуту из каталога проекта.');
        $this->line('Кэш настроек (1 ч): при смене токена в админке выполните: php artisan cache:clear');

        return self::SUCCESS;
    }
}
