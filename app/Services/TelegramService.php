<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\ConstructionStage;
use App\Models\Setting;
use App\Models\Task;

class TelegramService
{
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
        $text = "✅ *Задача создана*\n";
        $text .= "Название: {$title}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
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
        $text = "✏️ *Задача изменена*\n";
        $text .= "Название: {$title}\n";
        $text .= "Проект: {$projectName}\n";
        $text .= "Ответственный: {$responsible}\n";
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
