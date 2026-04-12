<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\TelegramGroupMessage;
use App\Services\OpenAiChatService;
use App\Services\TelegramGroupAssistantService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Вся тяжёлая обработка апдейта после быстрого 200 OK вебхука (меньше таймаутов у Telegram).
 */
class ProcessTelegramWebhookUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** OpenAI + снимок БД могут занять несколько минут — один воркер выполняет всё подряд. */
    public int $timeout = 300;

    public int $tries = 3;

    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public array $payload
    ) {}

    public function handle(): void
    {
        $message = $this->payload['message'] ?? $this->payload['edited_message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chat = $message['chat'] ?? null;
        if (! is_array($chat)) {
            return;
        }

        $chatType = $chat['type'] ?? '';
        if (! in_array($chatType, ['group', 'supergroup'], true)) {
            return;
        }

        $configuredChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        if ($configuredChatId === '') {
            return;
        }

        $incomingChatId = TelegramService::normalizeChatIdForStorage((string) ($chat['id'] ?? ''));
        if ($incomingChatId !== $configuredChatId) {
            return;
        }

        $from = $message['from'] ?? null;
        $token = Setting::get('telegram_bot_token');

        if (is_array($from) && ! empty($from['is_bot'])) {
            $ourBotId = TelegramService::getBotUserId($token);
            if (! $ourBotId || (int) ($from['id'] ?? 0) !== $ourBotId) {
                return;
            }
            $messageId = (int) ($message['message_id'] ?? 0);
            $text = isset($message['text']) ? (string) $message['text'] : null;
            $date = isset($message['date']) ? (int) $message['date'] : null;
            try {
                TelegramGroupMessage::query()->firstOrCreate(
                    [
                        'chat_id' => $incomingChatId,
                        'message_id' => $messageId,
                    ],
                    [
                        'from_user_id' => (int) $from['id'],
                        'from_username' => isset($from['username']) ? (string) $from['username'] : null,
                        'from_first_name' => 'ИИ-агент',
                        'text' => $text,
                        'message_date' => $date ? now()->setTimestamp($date) : null,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('telegram_group_message_store', ['e' => $e->getMessage()]);
            }

            return;
        }

        $messageId = (int) ($message['message_id'] ?? 0);
        $text = isset($message['text']) ? (string) $message['text'] : null;
        $date = isset($message['date']) ? (int) $message['date'] : null;

        try {
            TelegramGroupMessage::query()->firstOrCreate(
                [
                    'chat_id' => $incomingChatId,
                    'message_id' => $messageId,
                ],
                [
                    'from_user_id' => isset($from['id']) ? (int) $from['id'] : null,
                    'from_username' => isset($from['username']) ? (string) $from['username'] : null,
                    'from_first_name' => isset($from['first_name']) ? (string) $from['first_name'] : null,
                    'text' => $text,
                    'message_date' => $date ? now()->setTimestamp($date) : null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('telegram_group_message_store', ['e' => $e->getMessage()]);
        }

        if ($text === null || $text === '') {
            return;
        }

        $apiKey = app(OpenAiChatService::class)->getResolvedCredentials()['apiKey'];

        // Режим «все сообщения через ИИ» без ключа — раньше было полное молчание (условие ниже не выполнялось).
        if (Setting::get('telegram_group_ai_all', '1') === '1' && $token !== '') {
            if ($apiKey === '') {
                TelegramService::sendPlainMessage(
                    $token,
                    $incomingChatId,
                    'ИИ не отвечает: в CRM не задан API key (Настройки → блок «ИИ помощник», поле API key для провайдера).',
                    true,
                    'Бот (ошибка)'
                );

                return;
            }
        }

        if (Setting::get('telegram_group_ai_all', '1') === '1' && $token !== '' && $apiKey !== '') {
            $fromId = isset($from['id']) ? (int) $from['id'] : 0;
            $cooldownSec = max(0, (int) config('services.telegram_ai_cooldown_seconds', 0));
            if ($cooldownSec > 0 && ! Cache::add('telegram_ai_cd_'.$incomingChatId.'_'.$fromId, 1, $cooldownSec)) {
                Log::info('telegram_ai_skipped', ['reason' => 'cooldown', 'seconds' => $cooldownSec, 'chat' => $incomingChatId, 'from' => $fromId, 'message_id' => $messageId]);

                return;
            }
            if ($messageId > 0 && Cache::has('telegram_ai_done_'.$incomingChatId.'_'.$messageId)) {
                Log::info('telegram_ai_skipped', ['reason' => 'already_answered', 'chat' => $incomingChatId, 'message_id' => $messageId]);

                return;
            }
            // Старый ключ мог залипнуть на сутки при падении воркера — снимаем перед обработкой.
            Cache::forget('telegram_ai_queued_'.$incomingChatId.'_'.$messageId);

            try {
                (new ProcessTelegramGroupAiJob($text, $incomingChatId, $messageId))->handle();
            } catch (\Throwable $e) {
                Log::error('telegram_group_ai_handle', ['e' => $e->getMessage(), 'chat' => $incomingChatId]);
                throw $e;
            }

            return;
        }

        if (Setting::get('telegram_group_ai_crm', '1') === '1') {
            $crmQuestion = TelegramGroupAssistantService::extractCrmAiQuestion($text);
            if ($crmQuestion !== null && $crmQuestion !== '') {
                if ($token) {
                    if (! Cache::add('telegram_crm_ai_queued_'.$incomingChatId.'_'.$messageId, 1, 86400)) {
                        return;
                    }
                    try {
                        (new ProcessTelegramCrmAiJob($crmQuestion, $incomingChatId))->handle();
                    } catch (\Throwable $e) {
                        Cache::forget('telegram_crm_ai_queued_'.$incomingChatId.'_'.$messageId);
                        throw $e;
                    }
                }

                return;
            }
        }

        if (Setting::get('telegram_group_assistant_reply', '1') !== '1') {
            return;
        }

        if (! TelegramGroupAssistantService::isHelpCurrentInfoRequest($text)) {
            return;
        }

        if (! $token) {
            return;
        }

        if (! Cache::add('telegram_help_reply_'.$incomingChatId.'_'.$messageId, 1, 86400)) {
            return;
        }

        TelegramService::sendPlainMessage(
            $token,
            $incomingChatId,
            TelegramGroupAssistantService::HELP_CURRENT_INFO_REPLY,
            true,
            'Справка'
        );
    }

    /**
     * После исчерпания попыток — снять старые блокировки, иначе повторная обработка того же message_id невозможна.
     */
    public function failed(?\Throwable $e): void
    {
        $message = $this->payload['message'] ?? $this->payload['edited_message'] ?? null;
        if (! is_array($message)) {
            return;
        }
        $chat = $message['chat'] ?? null;
        if (! is_array($chat)) {
            return;
        }
        $incomingChatId = TelegramService::normalizeChatIdForStorage((string) ($chat['id'] ?? ''));
        $messageId = (int) ($message['message_id'] ?? 0);
        if ($incomingChatId === '' || $messageId <= 0) {
            return;
        }
        Cache::forget('telegram_ai_queued_'.$incomingChatId.'_'.$messageId);
        Cache::forget('telegram_crm_ai_queued_'.$incomingChatId.'_'.$messageId);
        Log::warning('telegram_webhook_job_failed', ['chat' => $incomingChatId, 'message_id' => $messageId, 'e' => $e?->getMessage()]);
    }
}
