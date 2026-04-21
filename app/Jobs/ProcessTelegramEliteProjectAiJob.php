<?php

namespace App\Jobs;

use App\Models\AiCompanyEvent;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TelegramGroupMessage;
use App\Services\OpenAiChatService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Новая группа «управление проектом Элитный»: сохраняем все сообщения (общая таблица),
 * затем ИИ извлекает важные события/задачи и фиксирует их в CRM.
 */
class ProcessTelegramEliteProjectAiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 240;

    public int $tries = 2;

    public function __construct(
        public string $text,
        public string $chatId,
        public int $messageId,
        public string $fromName,
        /** ISO-ish datetime string (server-local) */
        public string $messageDate,
        public ?string $messageType = null,
        public ?string $fileId = null,
        public ?string $fileName = null,
        public ?string $mimeType = null
    ) {}

    public function handle(): void
    {
        $projectId = (int) Setting::get('telegram_elite_project_id', 0);
        if ($projectId <= 0) {
            Log::info('telegram_elite_skip', ['reason' => 'no_project_id']);
            return;
        }

        Log::info('telegram_elite_start', [
            'chat' => $this->chatId,
            'message_id' => $this->messageId,
            'type' => $this->messageType,
            'file_name' => $this->fileName,
            'mime_type' => $this->mimeType,
            'text_len' => mb_strlen($this->text),
        ]);

        $cooldownSec = max(0, (int) Setting::get('telegram_elite_ai_cooldown_seconds', 0));
        if ($cooldownSec > 0 && ! Cache::add('telegram_elite_ai_cd_'.$this->chatId, 1, $cooldownSec)) {
            Log::info('telegram_elite_skip', ['reason' => 'cooldown', 'seconds' => $cooldownSec]);
            return;
        }

        if (Cache::has('telegram_elite_ai_done_'.$this->chatId.'_'.$this->messageId)) {
            return;
        }

        $project = Project::find($projectId);
        if (! $project) {
            Log::warning('telegram_elite_skip', ['reason' => 'project_not_found', 'project_id' => $projectId]);
            return;
        }

        $ai = app(OpenAiChatService::class);
        $c = $ai->getTextCredentials();
        if (($c['apiKey'] ?? '') === '') {
            Log::warning('telegram_elite_skip', ['reason' => 'no_ai_api_key']);
            return;
        }

        $media = $ai->getMediaCredentials();
        Log::info('telegram_elite_ai_config', [
            'text_provider' => $c['provider'] ?? null,
            'text_model' => $c['model'] ?? null,
            'media_provider' => $media['provider'] ?? null,
            'media_model' => $media['model'] ?? null,
        ]);

        $recent = TelegramGroupMessage::query()
            ->where('chat_id', TelegramService::normalizeChatIdForStorage($this->chatId))
            ->orderByDesc('id')
            ->limit(40)
            ->get(['from_first_name', 'from_username', 'text', 'message_date'])
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'from' => trim((string) ($m->from_first_name ?? '')) !== '' ? $m->from_first_name : ($m->from_username ?? '—'),
                'at' => $m->message_date?->format('Y-m-d H:i') ?? null,
                'text' => $m->text,
            ])
            ->all();

        $openTasks = Task::query()
            ->where('project_id', $project->id)
            ->whereIn('status', [
                Task::STATUS_IN_DEVELOPMENT,
                Task::STATUS_PROCESSING,
                Task::STATUS_EXECUTION,
            ])
            ->orderBy('status')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(25)
            ->get(['id', 'title', 'status', 'due_date', 'responsible_user_id'])
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'due_date' => $t->due_date?->format('Y-m-d'),
                'responsible_user_id' => $t->responsible_user_id,
            ])
            ->all();

        $companyEvents = AiCompanyEvent::query()
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'description', 'created_at'])
            ->map(fn (AiCompanyEvent $e) => [
                'id' => $e->id,
                'recorded_at' => $e->created_at?->format('Y-m-d H:i'),
                'description' => $e->description,
            ])
            ->values()
            ->all();

        $system = <<<'SYS'
Ты — помощник руководителя проекта. Твоя задача — по входящему сообщению из рабочей Telegram-группы определить, является ли оно важным для фиксации в CRM.

Правила:
- Если сообщение НЕ несёт управленческой ценности (болтовня, эмоции без фактов, мемы, приветствия) — верни important=false.
- Если в сообщении есть факты/решения/риски/изменения сроков/стоимости/договорённости/проблемы на объекте — important=true и создай 1–3 кратких событий.
- Если в сообщении содержится явное поручение или работа, которую нужно сделать — создай задачи (tasks_to_create).
- Не выдумывай детали. Если срок не указан — due_date=null.
- Всегда возвращай только валидный JSON без markdown и без пояснений.
 - Если приложен документ с извлечённым текстом — учитывай его как продолжение сообщения.

Формат ответа (JSON):
{
  "important": true|false,
  "events_to_create": ["..."],
  "tasks_to_create": [
    {"title":"...", "description":"...", "due_date":"YYYY-MM-DD"|null}
  ]
}
SYS;

        $attachmentText = null;
        if ($this->messageType === 'document' && $this->fileId) {
            $attachmentText = TelegramService::downloadTelegramFileText($this->fileId, $this->fileName, $this->mimeType);
        }
        if ($this->messageType === 'photo' && $this->fileId) {
            $bytes = TelegramService::downloadTelegramFileBytes($this->fileId, 1_800_000);
            if ($bytes) {
                $mime = $this->mimeType ?: 'image/jpeg';
                $vision = $ai->describeImageForEliteGroup($bytes, $mime, $this->text);
                if ($vision) {
                    $attachmentText = $vision;
                }
            }
        }

        Log::info('telegram_elite_attachment', [
            'type' => $this->messageType,
            'has_file' => (bool) $this->fileId,
            'attachment_text_len' => $attachmentText ? mb_strlen($attachmentText) : 0,
        ]);

        $fallbackEventForDocument = false;
        if ($this->messageType === 'document' && $attachmentText) {
            // Если документ пришёл без "важности" по мнению модели — всё равно фиксируем событие, чтобы не терять документы.
            $fallbackEventForDocument = true;
        }

        $context = [
            'project' => ['id' => $project->id, 'name' => $project->name],
            'open_tasks' => $openTasks,
            'company_events' => $companyEvents,
            'recent_group_messages' => $recent,
            'incoming' => [
                'from' => $this->fromName,
                'at' => $this->messageDate,
                'text' => $this->text,
                'attachment' => [
                    'type' => $this->messageType,
                    'file_name' => $this->fileName,
                    'mime_type' => $this->mimeType,
                    'extracted_text' => $attachmentText ? mb_substr($attachmentText, 0, 12000) : null,
                ],
            ],
        ];

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'system', 'content' => 'CONTEXT_JSON: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ['role' => 'user', 'content' => $this->text],
        ];

        try {
            $response = Http::connectTimeout(15)
                ->timeout(120)
                ->withToken($c['apiKey'])
                ->acceptJson()
                ->post("{$c['baseUrl']}/chat/completions", [
                    'model' => $c['model'],
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => 700,
                ]);

            if (! $response->successful()) {
                Log::warning('telegram_elite_ai_http', ['status' => $response->status(), 'body' => mb_substr((string) $response->body(), 0, 400)]);
                return;
            }

            $json = $response->json();
            $content = trim((string) ($json['choices'][0]['message']['content'] ?? ''));
            if ($content === '') {
                return;
            }

            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                // Попытка извлечь JSON из текста (если модель всё же добавила лишнее)
                $start = strpos($content, '{');
                $end = strrpos($content, '}');
                if ($start !== false && $end !== false && $end > $start) {
                    $decoded = json_decode(substr($content, $start, $end - $start + 1), true);
                }
            }

            if (! is_array($decoded)) {
                Log::warning('telegram_elite_ai_bad_json', ['content' => mb_substr($content, 0, 400)]);
                if ($fallbackEventForDocument) {
                    $summary = trim(preg_replace('/\s+/u', ' ', $attachmentText));
                    if (mb_strlen($summary) > 600) {
                        $summary = mb_substr($summary, 0, 600) . '…';
                    }
                    AiCompanyEvent::create([
                        'description' => 'Проект «' . $project->name . '»: Получен документ'
                            . ($this->fileName ? ' ' . $this->fileName : '')
                            . '. ' . ($summary !== '' ? ('Кратко: ' . $summary) : 'Текст не извлечён.'),
                        'created_by_user_id' => null,
                    ]);
                    Cache::put('telegram_elite_ai_done_'.$this->chatId.'_'.$this->messageId, 1, now()->addDays(7));
                }

                return;
            }

            $important = (bool) ($decoded['important'] ?? false);
            Log::info('telegram_elite_ai_decision', [
                'important' => $important,
                'events_n' => is_array($decoded['events_to_create'] ?? null) ? count($decoded['events_to_create']) : null,
                'tasks_n' => is_array($decoded['tasks_to_create'] ?? null) ? count($decoded['tasks_to_create']) : null,
            ]);
            if (! $important) {
                if ($fallbackEventForDocument) {
                    $summary = trim(preg_replace('/\s+/u', ' ', $attachmentText));
                    if (mb_strlen($summary) > 600) {
                        $summary = mb_substr($summary, 0, 600) . '…';
                    }
                    AiCompanyEvent::create([
                        'description' => 'Проект «' . $project->name . '»: Получен документ'
                            . ($this->fileName ? ' ' . $this->fileName : '')
                            . '. ' . ($summary !== '' ? ('Кратко: ' . $summary) : 'Текст не извлечён.'),
                        'created_by_user_id' => null,
                    ]);
                }

                Cache::put('telegram_elite_ai_done_'.$this->chatId.'_'.$this->messageId, 1, now()->addDays(7));
                return;
            }

            $events = $decoded['events_to_create'] ?? [];
            if (is_array($events)) {
                foreach (array_slice($events, 0, 3) as $e) {
                    $text = trim((string) $e);
                    if ($text === '') {
                        continue;
                    }
                    AiCompanyEvent::create([
                        'description' => 'Проект «'.$project->name.'»: '.$text,
                        'created_by_user_id' => null,
                    ]);
                }
            }

            $tasks = $decoded['tasks_to_create'] ?? [];
            if (is_array($tasks)) {
                foreach (array_slice($tasks, 0, 5) as $t) {
                    if (! is_array($t)) {
                        continue;
                    }
                    $title = trim((string) ($t['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    $desc = trim((string) ($t['description'] ?? ''));
                    $due = $t['due_date'] ?? null;
                    $due = is_string($due) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) ? $due : null;

                    Task::create([
                        'title' => $title,
                        'description' => $desc !== '' ? $desc : null,
                        'status' => Task::STATUS_PROCESSING,
                        'show_on_board' => true,
                        'client_id' => null,
                        'responsible_user_id' => null,
                        'project_id' => $project->id,
                        'budget' => null,
                        'due_date' => $due,
                        'sort_order' => (Task::where('status', Task::STATUS_PROCESSING)->max('sort_order') ?? 0) + 1,
                    ]);
                }
            }

            Cache::put('telegram_elite_ai_done_'.$this->chatId.'_'.$this->messageId, 1, now()->addDays(7));
        } catch (\Throwable $e) {
            Log::error('telegram_elite_ai_exception', ['e' => $e->getMessage()]);
        }
    }
}

