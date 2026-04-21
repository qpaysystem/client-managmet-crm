<?php

namespace App\Models;

use App\Services\TelegramService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TelegramGroupMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'message_id',
        'message_type',
        'from_user_id',
        'from_username',
        'from_first_name',
        'text',
        'caption',
        'file_id',
        'file_unique_id',
        'file_name',
        'mime_type',
        'file_size',
        'message_date',
    ];

    protected $casts = [
        'message_date' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Исходящее сообщение бота в группу (для дубля в админке «Сообщения Telegram»).
     *
     * @param  string  $successAuthorFirstName  Подпись при успешной доставке (например «ИИ-агент» или «Бот (ошибка)» для текста сбоя ИИ).
     */
    public static function recordBotOutgoing(
        string $chatId,
        ?int $telegramMessageId,
        string $text,
        bool $isSendFailure,
        string $successAuthorFirstName = 'ИИ-агент'
    ): void {
        $chatId = TelegramService::normalizeChatIdForStorage($chatId);
        $mid = $telegramMessageId ?? self::syntheticOutgoingMessageId();
        $author = $isSendFailure ? 'Бот (ошибка)' : $successAuthorFirstName;

        try {
            self::query()->firstOrCreate(
                [
                    'chat_id' => $chatId,
                    'message_id' => $mid,
                ],
                [
                    'from_user_id' => null,
                    'from_username' => null,
                    'from_first_name' => $author,
                    'text' => $text,
                    'message_date' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('telegram_group_bot_message_store', ['e' => $e->getMessage()]);
        }
    }

    private static function syntheticOutgoingMessageId(): int
    {
        return (int) (9000000000000000 + ((int) (microtime(true) * 1000000) % 1000000000000) + random_int(0, 999999));
    }
}
