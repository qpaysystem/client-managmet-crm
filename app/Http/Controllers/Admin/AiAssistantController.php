<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPrompt;
use App\Models\Client;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TelegramGroupMessage;
use App\Services\CrmDataSnapshotService;
use App\Services\OpenAiChatService;
use App\Services\TaskSituationReportService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.ai.index', compact('prompts', 'activePrompt', 'conversations', 'telegramChatId'));
    }

    /**
     * Сообщения группы Telegram, сохранённые webhook-ом (дубль переписки для админки).
     */
    public function telegramMessages(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:500',
        ]);
        $limit = (int) $request->input('limit', 400);

        $chatId = TelegramService::normalizeChatIdForStorage((string) Setting::get('telegram_chat_id', ''));
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
        ]);

        $conversation = AiConversation::create([
            'title' => $validated['title'] ?? null,
            'created_by_user_id' => Auth::id(),
            'client_id' => $validated['client_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'task_id' => $validated['task_id'] ?? null,
        ]);

        return response()->json(['ok' => true, 'conversation' => $conversation]);
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

        $context = $this->buildContext($conversation);
        $result = $chat->reply($conversation, $context);

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

        return $ctx;
    }
}

