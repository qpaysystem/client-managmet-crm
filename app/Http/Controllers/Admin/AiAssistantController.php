<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCompanyEvent;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPrompt;
use App\Models\Client;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TelegramGroupMessage;
use App\Models\User;
use App\Services\CrmDataSnapshotService;
use App\Services\MeetingAiService;
use App\Services\OpenAiChatService;
use App\Services\TaskSituationReportService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    /** Маркер в конце ответа ассистента: после него ждём «да»/«нет» для отправки в Telegram. */
    private const TELEGRAM_DIGEST_FOOTER = "\n\n---\nОтправить этот отчёт в Telegram? Ответьте «да» или «нет».";

    public function index(Request $request): View
    {
        $prompts = AiPrompt::orderByDesc('is_active')->orderByDesc('id')->get();
        $activePrompt = $prompts->firstWhere('is_active', true);

        $conversations = AiConversation::query()
            ->where('created_by_user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        $telegramChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        $telegramEliteChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_elite_chat_id', ''));

        $companyEvents = AiCompanyEvent::query()->orderByDesc('id')->limit(300)->get();

        return view('admin.ai.index', compact('prompts', 'activePrompt', 'conversations', 'telegramChatId', 'telegramEliteChatId', 'companyEvents'));
    }

    public function companyEventsIndex(): JsonResponse
    {
        $events = AiCompanyEvent::query()->orderByDesc('id')->limit(500)->get();

        return response()->json(['events' => $events]);
    }

    public function companyEventsShow(AiCompanyEvent $companyEvent): JsonResponse
    {
        return response()->json(['event' => $companyEvent]);
    }

    public function companyEventsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:50000',
        ]);

        $event = AiCompanyEvent::create([
            'description' => $validated['description'],
            'created_by_user_id' => Auth::id(),
        ]);

        return response()->json(['ok' => true, 'event' => $event]);
    }

    public function companyEventsUpdate(Request $request, AiCompanyEvent $companyEvent): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:50000',
        ]);

        $companyEvent->update(['description' => $validated['description']]);

        return response()->json(['ok' => true, 'event' => $companyEvent->fresh()]);
    }

    public function companyEventsDestroy(AiCompanyEvent $companyEvent): JsonResponse
    {
        $companyEvent->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Сообщения группы Telegram, сохранённые webhook-ом (дубль переписки для админки).
     */
    public function telegramMessages(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
            'chat_id' => 'nullable|string|max:50',
        ]);
        $limit = (int) $request->input('limit', 400);

        $defaultChatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
        $requestedChatId = TelegramService::normalizeChatIdForStorage((string) $request->input('chat_id', ''));
        $allowed = TelegramService::configuredGroupChatIds();

        $chatId = $defaultChatId;
        if ($requestedChatId !== '' && in_array($requestedChatId, $allowed, true)) {
            $chatId = $requestedChatId;
        }

        if ($chatId === '') {
            return response()->json([
                'ok' => true,
                'chat_id' => null,
                'messages' => [],
                'total_in_db' => 0,
                'loaded' => 0,
                'hint' => 'В настройках не задан Chat ID Telegram.',
            ]);
        }

        $total = TelegramGroupMessage::query()->where('chat_id', $chatId)->count();

        $outgoingInDb = TelegramGroupMessage::query()
            ->where('chat_id', $chatId)
            ->whereIn('from_first_name', ['ИИ-агент', 'Бот (ошибка)', 'Справка'])
            ->count();

        $rows = TelegramGroupMessage::query()
            ->where('chat_id', $chatId)
            ->orderByRaw('COALESCE(message_date, created_at) DESC')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $messages = $rows->map(function (TelegramGroupMessage $m) {
            $author = trim(($m->from_first_name ?: '') . ($m->from_username ? ' @' . $m->from_username : ''));
            if ($author === '') {
                $author = 'Участник';
            }

            return [
                'id' => $m->id,
                'telegram_message_id' => $m->message_id,
                'author' => $author,
                'text' => $m->text,
                'at' => $m->message_date?->format('d.m.Y H:i') ?? '',
            ];
        });

        $hint = null;
        if ($total > 0 && $outgoingInDb === 0) {
            $hint = 'Ответов бота в таблице пока нет (есть только входящие). Для новых ответов: очередь database, cron `schedule:run`, ключ ИИ в настройках. '
                .'Проверка на сервере: `php artisan telegram:doctor`. Старые ответы до появления дубля в БД не восстановить.';
        }

        return response()->json([
            'ok' => true,
            'chat_id' => $chatId,
            'messages' => $messages,
            'total_in_db' => $total,
            'outgoing_in_db' => $outgoingInDb,
            'loaded' => $messages->count(),
            'hint' => $hint,
        ]);
    }

    public function promptsIndex(): JsonResponse
    {
        $prompts = AiPrompt::orderByDesc('is_active')->orderByDesc('id')->get();
        return response()->json(['prompts' => $prompts]);
    }

    public function promptsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'system_prompt' => 'required|string|max:20000',
        ]);

        $prompt = AiPrompt::create([
            'title' => $validated['title'],
            'system_prompt' => $validated['system_prompt'],
            'is_active' => false,
            'created_by_user_id' => Auth::id(),
        ]);

        return response()->json(['ok' => true, 'prompt' => $prompt]);
    }

    public function promptsUpdate(Request $request, AiPrompt $prompt): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'system_prompt' => 'required|string|max:20000',
        ]);

        $prompt->update($validated);

        return response()->json(['ok' => true, 'prompt' => $prompt->fresh()]);
    }

    public function promptsActivate(AiPrompt $prompt): JsonResponse
    {
        AiPrompt::where('is_active', true)->update(['is_active' => false]);
        $prompt->update(['is_active' => true]);

        return response()->json(['ok' => true]);
    }

    public function conversationsIndex(): JsonResponse
    {
        $conversations = AiConversation::query()
            ->where('created_by_user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json(['conversations' => $conversations]);
    }

    public function conversationsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'kind' => 'nullable|in:general,meeting',
            'meeting_at' => 'nullable|date',
        ]);

        $kind = $validated['kind'] ?? 'general';
        $meetingAt = ! empty($validated['meeting_at'])
            ? Carbon::parse($validated['meeting_at'])
            : Carbon::now();

        $title = $validated['title'] ?? null;
        if ($kind === 'meeting' && $title === null) {
            $title = 'Совещание · '.$meetingAt->format('d.m.Y H:i');
        }

        $conversation = AiConversation::create([
            'title' => $title,
            'created_by_user_id' => Auth::id(),
            'client_id' => $validated['client_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'task_id' => $validated['task_id'] ?? null,
            'kind' => $kind,
            'meeting_at' => $kind === 'meeting' ? $meetingAt : null,
        ]);

        if ($kind === 'meeting') {
            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => MeetingAiService::welcomeMessage($meetingAt),
            ]);
        }

        return response()->json(['ok' => true, 'conversation' => $conversation->fresh()]);
    }

    /**
     * Подтверждение: создать задачи в CRM по стенограмме совещания (кнопка в интерфейсе).
     */
    public function applyMeetingTasks(Request $request, AiConversation $conversation, OpenAiChatService $chat): JsonResponse
    {
        $this->authorizeConversation($conversation);

        if ($conversation->kind !== 'meeting') {
            return response()->json(['ok' => false, 'message' => 'Это не диалог совещания.'], 422);
        }

        $fin = $this->finalizeMeetingTasks($conversation, $chat);

        $assistantMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => $fin['assistant_content'],
            'token_usage' => null,
        ]);
        $conversation->refresh()->touch();

        return response()->json([
            'ok' => true,
            'assistant_message' => $assistantMessage,
            'created_tasks' => $fin['created_tasks'],
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function conversationsShow(AiConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get();

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function messagesStore(Request $request, AiConversation $conversation, OpenAiChatService $chat): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $conversation->fill([
            'client_id' => $validated['client_id'] ?? $conversation->client_id,
            'project_id' => $validated['project_id'] ?? $conversation->project_id,
            'task_id' => $validated['task_id'] ?? $conversation->task_id,
        ]);
        $conversation->save();

        $userMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_USER,
            'content' => $validated['content'],
        ]);

        $content = $validated['content'];
        $lastAssistant = $this->lastAssistantBefore($conversation, $userMessage->id);

        if ($this->isPendingTelegramDigest($lastAssistant) && mb_strlen(trim($content)) <= 40) {
            if ($this->isShortAffirmative($content)) {
                $body = $this->extractDigestBody($lastAssistant);
                $send = TelegramService::sendPlainTextToNotificationsChat($body);
                $replyText = $send['ok']
                    ? 'Готово: отчёт отправлен в Telegram (тот же чат, что и уведомления в настройках).'
                    : 'Не удалось отправить в Telegram: ' . ($send['error'] ?? 'ошибка отправки.');
                $assistantMessage = AiMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => AiMessage::ROLE_ASSISTANT,
                    'content' => $replyText,
                    'token_usage' => null,
                ]);
                $conversation->touch();
                return response()->json([
                    'ok' => true,
                    'user_message' => $userMessage,
                    'assistant_message' => $assistantMessage,
                ]);
            }
            if ($this->isShortNegative($content)) {
                $assistantMessage = AiMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => AiMessage::ROLE_ASSISTANT,
                    'content' => 'Хорошо, в Telegram не отправляю.',
                    'token_usage' => null,
                ]);
                $conversation->touch();
                return response()->json([
                    'ok' => true,
                    'user_message' => $userMessage,
                    'assistant_message' => $assistantMessage,
                ]);
            }
        }

        if ($this->isTasksSituationReportRequest($content)) {
            $report = TaskSituationReportService::build();
            $full = $report . self::TELEGRAM_DIGEST_FOOTER;
            $assistantMessage = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => $full,
                'token_usage' => null,
            ]);
            $conversation->touch();
            return response()->json([
                'ok' => true,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
            ]);
        }

        if ($conversation->kind === 'meeting'
            && ! $conversation->meeting_finalized_at
            && $this->isMeetingApplyPhrase($content)) {
            $fin = $this->finalizeMeetingTasks($conversation->fresh(), $chat);
            $assistantMessage = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => $fin['assistant_content'],
                'token_usage' => null,
            ]);
            $conversation->refresh()->touch();

            return response()->json([
                'ok' => true,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'created_tasks' => $fin['created_tasks'],
            ]);
        }

        $context = $this->buildContext($conversation);
        if ($conversation->kind === 'meeting' && ! $conversation->meeting_finalized_at) {
            $result = $chat->replyMeeting($conversation, $context);
        } else {
            $result = $chat->reply($conversation, $context);
        }

        $assistantMessage = AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => $result['content'],
            'token_usage' => $result['usage'] ?? null,
        ]);

        $conversation->touch();

        return response()->json([
            'ok' => true,
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
        ]);
    }

    public function context(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:200',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 20);

        $clients = Client::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->search($q);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'balance', 'status']);

        $projects = Project::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);

        $tasks = Task::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'status', 'project_id', 'responsible_user_id', 'client_id']);

        return response()->json([
            'clients' => $clients,
            'projects' => $projects,
            'tasks' => $tasks,
        ]);
    }

    /**
     * @return array{assistant_content: string, created_tasks: array<int, array{id:int, title:string}>}
     */
    private function finalizeMeetingTasks(AiConversation $conversation, OpenAiChatService $chat): array
    {
        if ($conversation->meeting_finalized_at) {
            return [
                'assistant_content' => 'Задачи по этому совещанию уже внесены в CRM. Откройте новое совещение для нового протокола.',
                'created_tasks' => [],
            ];
        }

        $transcript = $this->buildMeetingTranscript($conversation);
        $ex = $chat->extractMeetingTasksJson($transcript);

        if (! $ex['ok']) {
            return [
                'assistant_content' => 'Не удалось подготовить задачи: '.($ex['error'] ?? 'ошибка ИИ').' Попробуйте ещё раз или сократите стенограмму.',
                'created_tasks' => [],
            ];
        }

        /** @var array<int, mixed> $items */
        $items = $ex['items'] ?? [];
        if (! is_array($items)) {
            return [
                'assistant_content' => 'ИИ вернул неожиданный формат. Напишите список задач короче и повторите подтверждение.',
                'created_tasks' => [],
            ];
        }

        $allowed = Task::statusesForBoard();
        $createdTasks = [];
        $projectId = $conversation->project_id;

        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $title = mb_substr($title, 0, 255);

            $status = (string) ($row['status'] ?? 'in_development');
            if (! in_array($status, $allowed, true)) {
                $status = Task::STATUS_IN_DEVELOPMENT;
            }

            $rid = isset($row['responsible_user_id']) ? (int) $row['responsible_user_id'] : null;
            if ($rid && ! User::query()->whereKey($rid)->exists()) {
                $rid = null;
            }

            $clientId = isset($row['client_id']) ? (int) $row['client_id'] : null;
            if ($clientId && ! Client::query()->whereKey($clientId)->exists()) {
                $clientId = null;
            }

            $due = null;
            if (! empty($row['due_date'])) {
                try {
                    $due = Carbon::parse((string) $row['due_date'])->format('Y-m-d');
                } catch (\Throwable) {
                    $due = null;
                }
            }

            $sortOrder = (int) Task::query()->where('status', $status)->max('sort_order') + 1;

            $description = isset($row['description']) ? (string) $row['description'] : null;
            if ($description !== null && $description === '') {
                $description = null;
            }

            $task = Task::create([
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'sort_order' => $sortOrder,
                'show_on_board' => true,
                'client_id' => $clientId,
                'responsible_user_id' => $rid,
                'project_id' => $projectId,
                'due_date' => $due,
            ]);

            TelegramService::notifyTaskCreated($task);

            $createdTasks[] = ['id' => $task->id, 'title' => $task->title];
        }

        if ($createdTasks === []) {
            return [
                'assistant_content' => 'Не удалось выделить ни одной задачи из стенограммы. Уточните формулировки в чате и снова нажмите «Создать задачи в CRM» или напишите «подтверждаю создание задач в CRM».',
                'created_tasks' => [],
            ];
        }

        $mt = $conversation->meeting_at ?? Carbon::now();
        $conversation->meeting_finalized_at = Carbon::now();
        $conversation->title = 'Совещание · '.$mt->format('d.m.Y H:i');
        $conversation->save();

        $lines = ['Созданы задачи в CRM:'];
        foreach ($createdTasks as $t) {
            $lines[] = '• #'.$t['id'].' '.$t['title'];
        }
        $lines[] = '';
        $lines[] = 'Чат сохранён; заголовок диалога — дата и время совещания.';

        return [
            'assistant_content' => implode("\n", $lines),
            'created_tasks' => $createdTasks,
        ];
    }

    private function buildMeetingTranscript(AiConversation $conversation): string
    {
        $parts = [];
        foreach ($conversation->messages()->orderBy('id')->get() as $m) {
            if ($m->role === AiMessage::ROLE_SYSTEM) {
                continue;
            }
            $who = $m->role === AiMessage::ROLE_USER ? 'Пользователь' : 'Ассистент';
            $parts[] = $who.":\n".$m->content;
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function isMeetingApplyPhrase(string $content): bool
    {
        $t = mb_strtolower(trim($content));
        if (mb_strlen($t) > 120) {
            return false;
        }

        return (bool) preg_match(
            '/подтверждаю\s+создание\s+задач\s+в\s+crm|создать\s+задачи\s+в\s+crm|внести\s+задачи\s+в\s+crm|зафиксировать\s+задачи/u',
            $t
        );
    }

    private function authorizeConversation(AiConversation $conversation): void
    {
        if ((int) $conversation->created_by_user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    private function lastAssistantBefore(AiConversation $conversation, int $beforeMessageId): ?AiMessage
    {
        return AiMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', AiMessage::ROLE_ASSISTANT)
            ->where('id', '<', $beforeMessageId)
            ->orderByDesc('id')
            ->first();
    }

    private function isPendingTelegramDigest(?AiMessage $lastAssistant): bool
    {
        return $lastAssistant !== null && str_ends_with($lastAssistant->content, self::TELEGRAM_DIGEST_FOOTER);
    }

    private function extractDigestBody(AiMessage $lastAssistant): string
    {
        $footer = self::TELEGRAM_DIGEST_FOOTER;
        $c = $lastAssistant->content;
        if (!str_ends_with($c, $footer)) {
            return $c;
        }
        return rtrim(mb_substr($c, 0, mb_strlen($c) - mb_strlen($footer)));
    }

    private function isTasksSituationReportRequest(string $content): bool
    {
        $t = mb_strtolower(trim($content));
        if (mb_strlen($t) < 10) {
            return false;
        }
        $hasTasks = (bool) preg_match('/задач/u', $t);
        $hasReport = (bool) preg_match('/отч[её]т|сводк|ситуац|обзор|положен/u', $t);
        return $hasTasks && $hasReport;
    }

    private function isShortAffirmative(string $content): bool
    {
        $t = mb_strtolower(trim($content));
        return (bool) preg_match('/^(да|ага|угу|yes|y|ок|окей|давай|отправь|отправить|отправляй)\s*[!?.]*$/u', $t);
    }

    private function isShortNegative(string $content): bool
    {
        $t = mb_strtolower(trim($content));
        return (bool) preg_match('/^(нет|не|no|не надо|не нужно)\s*[!?.]*$/u', $t);
    }

    private function buildContext(AiConversation $conversation): array
    {
        $client = $conversation->client_id ? Client::find($conversation->client_id) : null;
        $project = $conversation->project_id ? Project::find($conversation->project_id) : null;
        $task = $conversation->task_id ? Task::with(['responsibleUser', 'project', 'client'])->find($conversation->task_id) : null;

        $ctx = [
            'client' => $client ? [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'phone' => $client->phone,
                'email' => $client->email,
                'status' => $client->status,
                'balance' => (string) $client->balance,
            ] : null,
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
            ] : null,
            'task' => $task ? [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'status_label' => $task->status_label,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'responsible_user' => $task->responsibleUser ? [
                    'id' => $task->responsibleUser->id,
                    'name' => $task->responsibleUser->name,
                ] : null,
                'project' => $task->project ? ['id' => $task->project->id, 'name' => $task->project->name] : null,
                'client' => $task->client ? ['id' => $task->client->id, 'full_name' => $task->client->full_name] : null,
            ] : null,
        ];

        if (Setting::get('ai_include_crm_snapshot', '1') === '1') {
            try {
                $ctx['crm_data_snapshot'] = CrmDataSnapshotService::build();
            } catch (\Throwable) {
                // не ломаем чат при ошибке снимка
            }
        }

        $companyEvents = AiCompanyEvent::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (AiCompanyEvent $e) => [
                'id' => $e->id,
                'recorded_at' => $e->created_at?->format('Y-m-d H:i'),
                'description' => $e->description,
            ])
            ->values()
            ->all();

        if ($companyEvents !== []) {
            $ctx['company_events'] = $companyEvents;
        }

        return $ctx;
    }
}

