<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\OpenAiChatService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramGroupAiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 240;

    public int $tries = 2;

    public function __construct(
        public string $userMessage,
        public string $chatId
    ) {}

    public function handle(): void
    {
        $token = Setting::get('telegram_bot_token');
        if ($token === '' || $token === null) {
            Log::warning('telegram_group_ai_no_bot_token');

            return;
        }

        try {
            $ai = app(OpenAiChatService::class);
            Log::info('telegram_group_ai_start', [
                'chat' => $this->chatId,
                'msg_len' => mb_strlen($this->userMessage),
                'provider' => $ai->getResolvedCredentials()['provider'],
            ]);
            $answer = $ai->answerTelegramGroupAgent($this->userMessage);
            $raw = (string) ($answer['content'] ?? '');
            $out = trim(TelegramService::truncatePlainMessage($raw));
            if ($out === '') {
                $out = 'Пустой ответ ИИ — попробуйте переформулировать вопрос.';
            }
            $ok = TelegramService::sendPlainMessage($token, $this->chatId, $out);
            Log::info('telegram_group_ai_finish', ['chat' => $this->chatId, 'telegram_ok' => $ok, 'out_len' => mb_strlen($out)]);
        } catch (\Throwable $e) {
            Log::error('telegram_group_agent_job', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            TelegramService::sendPlainMessage(
                $token,
                $this->chatId,
                'Не удалось получить ответ ИИ. Попробуйте позже.',
                true,
                'Бот (ошибка)'
            );
        }
    }
}
