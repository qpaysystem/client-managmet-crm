<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelegramWebhookUpdateJob;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Long polling getUpdates — обход проблем доставки вебхука с серверов Telegram.
 * Нельзя использовать одновременно с вебхуком: перед стартом удалите вебхук (--delete-webhook).
 */
class TelegramLongPollCommand extends Command
{
    protected $signature = 'telegram:poll
                            {--delete-webhook : Удалить вебхук (обязательно, если getUpdates конфликтует с webhook)}
                            {--drop-pending : Вместе с --delete-webhook сбросить накопившиеся апдейты у Telegram}
                            {--timeout=50 : Long poll timeout 0–50 сек}';

    protected $description = 'Long polling: getUpdates → та же очередь, что и вебхук. Без запущенного процесса апдейты не идут (или включите вебхук).';

    private const OFFSET_CACHE_KEY = 'telegram_long_poll_offset';

    public function handle(): int
    {
        $token = trim((string) Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            $this->error('В настройках CRM не задан telegram_bot_token.');

            return self::FAILURE;
        }

        $timeout = max(0, min(50, (int) $this->option('timeout')));
        $base = 'https://api.telegram.org/bot'.$token;

        $webhookInfo = Http::timeout(15)->get($base.'/getWebhookInfo');
        $webhookUrl = '';
        if ($webhookInfo->successful()) {
            $j = $webhookInfo->json();
            $webhookUrl = is_array($j) ? (string) ($j['result']['url'] ?? '') : '';
        }

        if ($webhookUrl !== '' && ! $this->option('delete-webhook')) {
            $this->error('У бота установлен вебхук: '.$webhookUrl);
            $this->line('Запустите: php artisan telegram:poll --delete-webhook');
            $this->line('(getUpdates и webhook в Telegram взаимоисключающие.)');

            return self::FAILURE;
        }

        if ($this->option('delete-webhook')) {
            $params = $this->option('drop-pending') ? ['drop_pending_updates' => 'true'] : [];
            $del = Http::timeout(15)->get($base.'/deleteWebhook', $params);
            if (! $del->successful()) {
                $this->error('deleteWebhook: HTTP '.$del->status());

                return self::FAILURE;
            }
            $dj = $del->json();
            if (empty($dj['ok'])) {
                $this->error('deleteWebhook: '.substr($del->body(), 0, 300));

                return self::FAILURE;
            }
            $this->info('Вебхук удалён. Long polling… (Ctrl+C останавливает приём — тогда снова не будет сообщений, пока не запустите эту команду или не вернёте вебхук).');
        } else {
            $this->warn('Убедитесь, что вебхук снят (иначе getUpdates вернёт конфликт).');
        }

        $offset = (int) Cache::get(self::OFFSET_CACHE_KEY, 0);
        $this->info('Очередь: database — как у вебхука. offset='.$offset.' (кэш). Ожидание апдейтов (timeout='.$timeout.'s)…');

        $httpTimeout = $timeout > 0 ? $timeout + 25 : 30;

        while (true) {
            $response = Http::connectTimeout(15)
                ->timeout($httpTimeout)
                ->get($base.'/getUpdates', [
                    'offset' => $offset,
                    'limit' => 100,
                    'timeout' => $timeout,
                    'allowed_updates' => '["message","edited_message"]',
                ]);

            if (! $response->successful()) {
                $this->warn('getUpdates HTTP '.$response->status().' — пауза 5 с');
                sleep(5);

                continue;
            }

            $data = $response->json();
            if (! is_array($data) || empty($data['ok'])) {
                $desc = is_array($data) ? (string) ($data['description'] ?? json_encode($data)) : $response->body();
                if (str_contains($desc, 'Conflict') || str_contains($desc, 'getUpdates')) {
                    $this->error('Конфликт: активен вебхук. Остановите процесс и выполните: php artisan telegram:poll --delete-webhook');

                    return self::FAILURE;
                }
                $this->warn('getUpdates: '.$desc.' — пауза 5 с');
                sleep(5);

                continue;
            }

            $updates = $data['result'] ?? [];
            if (! is_array($updates)) {
                sleep(1);

                continue;
            }

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }
                $updateId = (int) ($update['update_id'] ?? 0);
                if ($updateId > 0) {
                    $offset = $updateId + 1;
                }

                if (! TelegramService::shouldQueueConfiguredGroupUpdate($update)) {
                    continue;
                }

                ProcessTelegramWebhookUpdateJob::dispatch($update)->onConnection('database');
                $this->line('update_id='.$updateId.' → job');
            }

            if ($offset > 0) {
                Cache::put(self::OFFSET_CACHE_KEY, $offset, now()->addDays(90));
            }
        }
    }
}
