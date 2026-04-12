<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramWebhookUpdateJob;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    /**
     * Минимальная работа: проверка секрета, фильтр группы/chat_id, сразу 200 OK.
     * Обработка сообщений и ИИ — в {@see ProcessTelegramWebhookUpdateJob} (очередь database).
     */
    public function handle(Request $request): JsonResponse
    {
        $secret = Setting::get('telegram_webhook_secret', '');
        if ($secret !== '') {
            $incoming = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', $request->query('secret', ''));
            if (! hash_equals($secret, $incoming)) {
                abort(403);
            }
        }

        $payload = self::decodeTelegramPayload($request);

        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chat = $message['chat'] ?? null;
        if (! is_array($chat)) {
            return response()->json(['ok' => true]);
        }

        $chatType = $chat['type'] ?? '';
        if (! in_array($chatType, ['group', 'supergroup'], true)) {
            return response()->json(['ok' => true]);
        }

        $configuredChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        if ($configuredChatId === '') {
            return response()->json(['ok' => true]);
        }

        $incomingChatId = TelegramService::normalizeChatIdForStorage((string) ($chat['id'] ?? ''));
        if ($incomingChatId !== $configuredChatId) {
            return response()->json(['ok' => true]);
        }

        ProcessTelegramWebhookUpdateJob::dispatch($payload)->onConnection('database');

        return response()->json(['ok' => true]);
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
