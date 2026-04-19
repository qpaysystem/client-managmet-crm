<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\OpenAiChatService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramSendHourlyConstructionThesisCommand extends Command
{
    protected $signature = 'telegram:send-hourly-construction-thesis
                            {--dry-run : Вывести текст в консоль, в Telegram не слать}
                            {--force : Для проверки: не смотреть настройку «включено» (с --dry-run)}';

    protected $description = 'Отправить в группу Telegram один смешной тезис про стройку (ИИ). Вызывается по расписанию раз в час.';

    public function handle(): int
    {
        if (! $this->option('force') && Setting::get('telegram_hourly_construction_thesis', '0') !== '1') {
            return self::SUCCESS;
        }

        $token = trim((string) Setting::get('telegram_bot_token', ''));
        $chatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));

        if ($token === '' || $chatId === '') {
            Log::warning('telegram_hourly_thesis_skip', ['reason' => 'no_token_or_chat']);

            return self::FAILURE;
        }

        $ai = new OpenAiChatService;
        $answer = $ai->generateHourlyConstructionThesis();
        $text = trim($answer['content'] ?? '');

        if ($text === '') {
            Log::warning('telegram_hourly_thesis_skip', ['reason' => 'empty_ai']);

            return self::FAILURE;
        }

        $body = "🏗 Тезис часа\n\n".TelegramService::truncatePlainMessage($text, 3500);

        if ($this->option('dry-run')) {
            $this->line($body);

            return self::SUCCESS;
        }

        $ok = TelegramService::sendPlainMessage($token, $chatId, $body, true, 'Тезис часа');

        if (! $ok) {
            Log::warning('telegram_hourly_thesis_send_failed');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
