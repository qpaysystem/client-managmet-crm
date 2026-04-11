<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TelegramGroupMessage;
use App\Services\OpenAiChatService;
use App\Services\TelegramGroupAssistantService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = Setting::get('telegram_webhook_secret', '');
        if ($secret !== '') {
            $incoming = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', $request->query('secret', ''));
            if (!hash_equals($secret, $incoming)) {
                abort(403);
            }
        }

        $payload = self::decodeTelegramPayload($request);

        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (!is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chat = $message['chat'] ?? null;
        if (!is_array($chat)) {
            return response()->json(['ok' => true]);
        }

        $chatType = $chat['type'] ?? '';
        if (!in_array($chatType, ['group', 'supergroup'], true)) {
            return response()->json(['ok' => true]);
        }

        $configuredChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        if ($configuredChatId === '') {
            return response()->json(['ok' => true]);
        }

        $incomingChatId = TelegramService::normalizeChatIdForStorage((string) ($chat['id'] ?? ''));
        if ($incomingChatId !== $configuredChatId) {
            if (config('app.debug')) {
                Log::debug('telegram_webhook_chat_mismatch', [
                    'incoming' => $incomingChatId,
                    'configured' => $configuredChatId,
                ]);
            }
            return response()->json(['ok' => true]);
        }

        $from = $message['from'] ?? null;
        $token = Setting::get('telegram_bot_token');

        if (is_array($from) && ! empty($from['is_bot'])) {
            $ourBotId = TelegramService::getBotUserId($token);
            if (! $ourBotId || (int) ($from['id'] ?? 0) !== $ourBotId) {
                return response()->json(['ok' => true]);
            }
            // Сообщения нашего бота в группе — в дубль переписки (ответы ИИ/справка), без повторного запуска ИИ.
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

            return response()->json(['ok' => true]);
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
            return response()->json(['ok' => true]);
        }

        $provider = (string) Setting::get('ai_provider', 'openai');
        if (! in_array($provider, ['openai', 'deepseek'], true)) {
            $provider = 'openai';
        }
        $apiKey = (string) Setting::get('ai_api_key', Setting::get('openai_api_key', config("services.{$provider}.api_key")));

        // Режим по умолчанию: каждое текстовое сообщение → ИИ + снимок БД; оффтоп → фиксированный отказ.
        if (Setting::get('telegram_group_ai_all', '1') === '1' && $token !== '' && $apiKey !== '') {
            $fromId = isset($from['id']) ? (int) $from['id'] : 0;
            $coolKey = 'telegram_ai_cd_' . $incomingChatId . '_' . $fromId;
            if (! Cache::add($coolKey, 1, 2)) {
                return response()->json(['ok' => true]);
            }

            $chatId = $incomingChatId;
            $dispatchText = $text;
            dispatch(function () use ($dispatchText, $token, $chatId): void {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(240);
                }
                try {
                    $ai = app(OpenAiChatService::class);
                    $answer = $ai->answerTelegramGroupAgent($dispatchText);
                    $out = TelegramWebhookController::truncateTelegramMessage($answer['content']);
                    TelegramService::sendPlainMessage($token, $chatId, $out);
                } catch (\Throwable $e) {
                    Log::error('telegram_group_agent_after_response', ['message' => $e->getMessage()]);
                    TelegramService::sendPlainMessage(
                        $token,
                        $chatId,
                        'Не удалось получить ответ ИИ. Попробуйте позже.',
                        true,
                        'Бот (ошибка)'
                    );
                }
            })->afterResponse();

            return response()->json(['ok' => true]);
        }

        // Устаревший режим: только /вопрос, /ask и «естественные» вопросы по CRM.
        if (Setting::get('telegram_group_ai_crm', '1') === '1') {
            $crmQuestion = TelegramGroupAssistantService::extractCrmAiQuestion($text);
            if ($crmQuestion !== null && $crmQuestion !== '') {
                if ($token) {
                    $chatId = $incomingChatId;
                    dispatch(function () use ($crmQuestion, $token, $chatId): void {
                        if (function_exists('set_time_limit')) {
                            @set_time_limit(240);
                        }
                        try {
                            $ai = app(OpenAiChatService::class);
                            $answer = $ai->answerCrmQuestion($crmQuestion);
                            $out = TelegramWebhookController::truncateTelegramMessage($answer['content']);
                            TelegramService::sendPlainMessage($token, $chatId, $out);
                        } catch (\Throwable $e) {
                            Log::error('telegram_crm_ai_after_response', [
                                'message' => $e->getMessage(),
                            ]);
                            TelegramService::sendPlainMessage(
                                $token,
                                $chatId,
                                'Не удалось получить ответ ИИ. Попробуйте позже.',
                                true,
                                'Бот (ошибка)'
                            );
                        }
                    })->afterResponse();
                }

                return response()->json(['ok' => true]);
            }
        }

        if (Setting::get('telegram_group_assistant_reply', '1') !== '1') {
            return response()->json(['ok' => true]);
        }

        if (!TelegramGroupAssistantService::isHelpCurrentInfoRequest($text)) {
            return response()->json(['ok' => true]);
        }

        if (!$token) {
            return response()->json(['ok' => true]);
        }

        TelegramService::sendPlainMessage(
            $token,
            $incomingChatId,
            TelegramGroupAssistantService::HELP_CURRENT_INFO_REPLY,
            true,
            'Справка'
        );

        return response()->json(['ok' => true]);
    }

    private static function truncateTelegramMessage(string $text, int $maxLen = 4000): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return mb_substr($text, 0, $maxLen - 20) . "\n…(обрезано)";
    }

    /**
     * Большие отрицательные chat_id в JSON без потери точности (иначе id «плывёт» и не совпадает с настройкой).
     */
    private static function decodeTelegramPayload(Request $request): array
    {
        $raw = $request->getContent();
        if ($raw !== '') {
            try {
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\Throwable) {
                // fallback
            }
        }
        $fallback = $request->json()->all();
        if ($fallback !== []) {
            return $fallback;
        }

        return $request->all();
    }

}
