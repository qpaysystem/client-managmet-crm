<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TelegramGroupMessage;
use App\Services\TelegramGroupAssistantService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = $request->all();
        }

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

        $configuredChatId = trim((string) Setting::get('telegram_chat_id', ''));
        if ($configuredChatId === '') {
            return response()->json(['ok' => true]);
        }

        $incomingChatId = (string) ($chat['id'] ?? '');
        if ($incomingChatId !== $configuredChatId) {
            return response()->json(['ok' => true]);
        }

        $from = $message['from'] ?? null;
        if (is_array($from) && !empty($from['is_bot'])) {
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

        if (Setting::get('telegram_group_assistant_reply', '1') !== '1') {
            return response()->json(['ok' => true]);
        }

        if ($text === null || $text === '') {
            return response()->json(['ok' => true]);
        }

        if (!TelegramGroupAssistantService::isHelpCurrentInfoRequest($text)) {
            return response()->json(['ok' => true]);
        }

        $token = Setting::get('telegram_bot_token');
        if (!$token) {
            return response()->json(['ok' => true]);
        }

        TelegramService::sendPlainMessage(
            $token,
            $incomingChatId,
            TelegramGroupAssistantService::HELP_CURRENT_INFO_REPLY
        );

        return response()->json(['ok' => true]);
    }
}
