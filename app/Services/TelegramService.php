<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\ConstructionStage;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TelegramGroupMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /** Обрезка текста под лимит Telegram (без разметки). */
    public static function truncatePlainMessage(string $text, int $maxLen = 4000): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        return mb_substr($text, 0, $maxLen - 20)."\n…(обрезано)";
    }

    /** Единый формат chat_id для БД и настроек (как в вебхуке). */
    public static function normalizeChatIdForStorage(string $id): string
    {
        return trim(str_replace(["\xc2\xa0", ' '], '', $id));
    }

    /**
     * Тот же фильтр, что у POST /telegram/webhook: только группа/супергруппа и совпадение с одним из настроенных chat_id.
     *
     * @param  array<string, mixed>  $payload  Объект Update от Telegram (message / edited_message).
     */
    public static function shouldQueueConfiguredGroupUpdate(array $payload): bool
    {
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        if (! is_array($message)) {
            return false;
        }
        $chat = $message['chat'] ?? null;
        if (! is_array($chat)) {
            return false;
        }
        $chatType = $chat['type'] ?? '';
        if (! in_array($chatType, ['group', 'supergroup'], true)) {
            return false;
        }
        $allowedChatIds = self::configuredGroupChatIds();
        if ($allowedChatIds === []) {
            return false;
        }
        $incomingChatId = self::normalizeChatIdForStorage((string) ($chat['id'] ?? ''));

        return in_array($incomingChatId, $allowedChatIds, true);
    }

    /**
     * @return array<int, string>
     */
    public static function configuredGroupChatIds(): array
    {
        $ids = [
            self::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', '')),
            self::normalizeChatIdForStorage((string) Setting::get('telegram_elite_chat_id', '')),
        ];

        $out = [];
        foreach ($ids as $id) {
            if ($id !== '') {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }

    /** ID пользователя бота по токену (кэш), чтобы отличать нашего бота от чужих в группе. */
    public static function getBotUserId(?string $token = null): ?int
    {
        $token = $token ?? (string) Setting::get('telegram_bot_token', '');
        if ($token === '') {
            return null;
        }
        $cacheKey = 'telegram_bot_user_id_'.hash('sha256', $token);

        return Cache::remember($cacheKey, 86400, function () use ($token) {
            $url = "https://api.telegram.org/bot{$token}/getMe";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $decoded = json_decode((string) $response, true);
            if (! is_array($decoded) || empty($decoded['ok']) || ! isset($decoded['result']['id'])) {
                return null;
            }

            return (int) $decoded['result']['id'];
        });
    }

    private static function escapeMarkdown(string $s): string
    {
        return str_replace(['_', '*', '[', ']', '`'], ['\_', '\*', '\[', '\]', '\`'], $s);
    }

    public static function notifyTransaction(BalanceTransaction $transaction): bool
    {
        if (Setting::get('telegram_notify_transactions', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $typeLabel = $transaction->operation_type_label ?? ($transaction->type === 'deposit' ? 'Пополнение' : 'Списание');
        $sign = $transaction->type === 'deposit' ? '+' : '−';
        $currency = Setting::get('currency', 'RUB');
        $text = "📋 *Транзакция*\n";
        $text .= "Клиент: {$transaction->client->full_name}\n";
        $text .= "Тип: {$typeLabel}\n";
        $text .= "Сумма: {$sign}" . number_format($transaction->amount, 2) . " {$currency}\n";
        $text .= "Баланс после: " . number_format($transaction->balance_after, 2) . " {$currency}\n";
        if ($transaction->comment) {
            $text .= "Комментарий: {$transaction->comment}\n";
        }
        if ($transaction->product) {
            $text .= "Залог: {$transaction->product->name}\n";
        }
        $text .= "\n_" . $transaction->created_at->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyTaskCreated(Task $task): bool
    {
        if (Setting::get('telegram_notify_tasks', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $task->load(['responsibleUser', 'client', 'project']);
        $title = self::escapeMarkdown($task->title);
        $projectName = $task->project ? self::escapeMarkdown($task->project->name) : '—';
        $responsible = $task->responsibleUser ? self::escapeMarkdown($task->responsibleUser->name) : '—';
        $status = $task->status_label;
        $due = $task->due_date ? $task->due_date->format('d.m.Y') : '—';
        $description = $task->description ? self::escapeMarkdown(\Illuminate\Support\Str::limit($task->description, 800)) : null;
        $text = "✅ *Задача создана*\n";
        $text .= "Название: {$title}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
        if ($description) {
            $text .= "Описание: {$description}\n";
        }
        $text .= "Статус: {$status}\n";
        $text .= "Дата окончания: {$due}\n";
        $text .= "\n_" . $task->created_at->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyTaskUpdated(Task $task): bool
    {
        if (Setting::get('telegram_notify_tasks', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $task->load(['responsibleUser', 'client', 'project']);
        $title = self::escapeMarkdown($task->title);
        $projectName = $task->project ? self::escapeMarkdown($task->project->name) : '—';
        $responsible = $task->responsibleUser ? self::escapeMarkdown($task->responsibleUser->name) : '—';
        $status = $task->status_label;
        $due = $task->due_date ? $task->due_date->format('d.m.Y') : '—';
        $description = $task->description ? self::escapeMarkdown(\Illuminate\Support\Str::limit($task->description, 800)) : null;
        $text = "✏️ *Задача изменена*\n";
        $text .= "Название: {$title}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
        if ($description) {
            $text .= "Описание: {$description}\n";
        }
        $text .= "Статус: {$status}\n";
        $text .= "Дата окончания: {$due}\n";
        $text .= "\n_" . now()->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyTaskDeleted(string $title, ?string $projectName): bool
    {
        if (Setting::get('telegram_notify_tasks', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $title = self::escapeMarkdown($title);
        $projectName = $projectName ? self::escapeMarkdown($projectName) : '—';
        $text = "🗑 *Задача удалена*\n";
        $text .= "Название: {$title}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "\n_" . now()->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyConstructionStageCreated(ConstructionStage $stage): bool
    {
        if (Setting::get('telegram_notify_stages', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $stage->load(['client', 'project']);
        $name = self::escapeMarkdown($stage->name);
        $projectName = $stage->project ? self::escapeMarkdown($stage->project->name) : '—';
        $responsible = $stage->client ? self::escapeMarkdown($stage->client->full_name) : '—';
        $status = $stage->status_label;
        $planEnd = $stage->planned_end_date ? $stage->planned_end_date->format('d.m.Y') : '—';
        $text = "🏗 *Этап строительства добавлен*\n";
        $text .= "Этап: {$name}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
        $text .= "Статус: {$status}\n";
        $text .= "Дата окончания (план): {$planEnd}\n";
        $text .= "\n_" . $stage->created_at->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyConstructionStageUpdated(ConstructionStage $stage): bool
    {
        if (Setting::get('telegram_notify_stages', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $stage->load(['client', 'project']);
        $name = self::escapeMarkdown($stage->name);
        $projectName = $stage->project ? self::escapeMarkdown($stage->project->name) : '—';
        $responsible = $stage->client ? self::escapeMarkdown($stage->client->full_name) : '—';
        $status = $stage->status_label;
        $planEnd = $stage->planned_end_date ? $stage->planned_end_date->format('d.m.Y') : '—';
        $text = "✏️ *Этап строительства изменён*\n";
        $text .= "Этап: {$name}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
        $text .= "Статус: {$status}\n";
        $text .= "Дата окончания (план): {$planEnd}\n";
        $text .= "\n_" . now()->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    public static function notifyConstructionStageDeleted(string $stageName, ?string $projectName): bool
    {
        if (Setting::get('telegram_notify_stages', '0') !== '1') {
            return false;
        }
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return false;
        }
        $stageName = self::escapeMarkdown($stageName);
        $projectName = $projectName ? self::escapeMarkdown($projectName) : '—';
        $text = "🗑 *Этап строительства удалён*\n";
        $text .= "Этап: {$stageName}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "\n_" . now()->format('d.m.Y H:i') . "_";
        return self::sendMessage($token, $chatId, $text);
    }

    /**
     * Отправка длинного текста без разметки (для сводок из ИИ-помощника и т.п.).
     * Использует тот же chat_id, что и уведомления (группа/канал из настроек).
     */
    public static function sendPlainTextToNotificationsChat(string $text): array
    {
        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (!$token || !$chatId) {
            return [
                'ok' => false,
                'error' => 'В настройках не заданы telegram_bot_token или telegram_chat_id.',
            ];
        }
        $len = mb_strlen($text, 'UTF-8');
        for ($offset = 0; $offset < $len; $offset += 4000) {
            $chunk = mb_substr($text, $offset, 4000, 'UTF-8');
            if (!self::sendPlainMessage($token, $chatId, $chunk)) {
                return ['ok' => false, 'error' => 'Telegram API не принял сообщение (проверьте токен и chat_id).'];
            }
        }
        return ['ok' => true];
    }

    /**
     * @param  bool  $recordInHistory  Дублировать в telegram_group_messages (вкладка «Сообщения Telegram» в админке).
     * @param  string  $outgoingAuthorFirstName  Подпись исходящего сообщения при успешной отправке (для ответов об ошибке ИИ — «Бот (ошибка)»).
     */
    public static function sendPlainMessage(
        string $token,
        string $chatId,
        string $text,
        bool $recordInHistory = true,
        string $outgoingAuthorFirstName = 'ИИ-агент'
    ): bool {
        $chatId = self::normalizeChatIdForStorage($chatId);
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $useJson = $json !== false;
        if (! $useJson) {
            Log::warning('telegram_send_plain_json_encode');
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $useJson ? $json : http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ];
        if ($useJson) {
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json; charset=utf-8'];
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $response, true);
        $ok = $httpCode === 200 && is_array($decoded) && ($decoded['ok'] ?? false);
        $msgId = ($ok && is_array($decoded) && isset($decoded['result']['message_id']))
            ? (int) $decoded['result']['message_id']
            : null;

        if ($recordInHistory) {
            if ($ok && $msgId !== null) {
                TelegramGroupMessage::recordBotOutgoing($chatId, $msgId, $text, false, $outgoingAuthorFirstName);
            } elseif ($ok) {
                TelegramGroupMessage::recordBotOutgoing($chatId, null, $text, false, $outgoingAuthorFirstName);
            } else {
                $errDetail = 'HTTP '.$httpCode;
                if (is_array($decoded)) {
                    $errDetail .= ': '.($decoded['description'] ?? json_encode($decoded, JSON_UNESCAPED_UNICODE));
                } else {
                    $errDetail .= ': '.substr((string) $response, 0, 400);
                }
                TelegramGroupMessage::recordBotOutgoing(
                    $chatId,
                    null,
                    'Не отправлено в Telegram. '.$errDetail,
                    true
                );
            }
        }

        if ($httpCode !== 200) {
            Log::warning('telegram_send_plain_http', ['http' => $httpCode, 'body' => substr((string) $response, 0, 500)]);
            return false;
        }
        if (!$ok && is_array($decoded)) {
            Log::warning('telegram_send_plain_api', ['description' => $decoded['description'] ?? $response]);
        }

        return $ok;
    }

    public static function sendMessage(string $token, string $chatId, string $text): bool
    {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $httpCode === 200;
    }
}
