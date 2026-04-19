<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPrompt;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    /**
     * Единые настройки провайдера для админ-чата, Telegram и CRM-вопросов (DeepSeek / OpenAI из настроек).
     *
     * @return array{provider: string, apiKey: string, model: string, baseUrl: string}
     */
    public function getResolvedCredentials(): array
    {
        return $this->resolveCredentials();
    }

    /**
     * @return array{provider: string, apiKey: string, model: string, baseUrl: string}
     */
    private function resolveCredentials(): array
    {
        $provider = (string) Setting::get('ai_provider', 'openai');
        if (! in_array($provider, ['openai', 'deepseek'], true)) {
            $provider = 'openai';
        }

        $apiKey = (string) Setting::get('ai_api_key', Setting::get('openai_api_key', config("services.{$provider}.api_key")));
        $model = (string) Setting::get('ai_model', Setting::get('openai_model', config("services.{$provider}.model")));
        $baseUrlRaw = (string) Setting::get('ai_base_url', Setting::get('openai_base_url', config("services.{$provider}.base_url")));
        $baseUrl = $this->normalizeBaseUrl($baseUrlRaw, $provider);

        return [
            'provider' => $provider,
            'apiKey' => $apiKey,
            'model' => $model,
            'baseUrl' => $baseUrl,
        ];
    }

    /**
     * Ответ на вопрос по данным CRM (один запрос, контекст — снимок БД).
     *
     * @return array{content:string,usage?:array}
     */
    public function answerCrmQuestion(string $userQuestion): array
    {
        $userQuestion = trim($userQuestion);
        if ($userQuestion === '') {
            return ['content' => 'Задайте вопрос текстом (например: сколько свободных квартир).'];
        }

        $system = 'Ты — аналитик CRM. Ниже JSON — актуальный снимок данных из базы (агрегаты и ограниченные выборки, только чтение). '
            . 'Ответь на вопрос пользователя по-русски, кратко и по фактам из данных. '
            . 'Если в данных нет нужного — так и скажи. Не выдумывай цифры.';

        return $this->completeWithCrmSnapshot($userQuestion, $system, 0.2, 'OpenAI CRM question');
    }

    /**
     * Агент группы Telegram: снимок CRM + отказ по оффтопу (фиксированная фраза).
     *
     * @return array{content:string,usage?:array}
     */
    public function answerTelegramGroupAgent(string $userMessage): array
    {
        $userMessage = trim($userMessage);
        if ($userMessage === '') {
            return ['content' => TelegramGroupAssistantService::OFF_TOPIC_REPLY];
        }

        $refusal = TelegramGroupAssistantService::OFF_TOPIC_REPLY;
        $system = "Ты — агент CRM. Ниже JSON CRM_DATA_JSON — снимок базы: клиенты (в т.ч. sample), проекты со счётчиками квартир, квартиры по статусам и выборки (свободные, залог, продажи), транзакции с проектом/продуктом, задачи (открытые и недавно завершённые), этапы строительства, инвестиции клиентов в проекты, справочники custom_fields и products.\n\n"
            . "Это В компетенции — отвечай по JSON, не отказывай: «сколько свободных/свободно квартир», квартиры по статусам и проектам, "
            . "транзакции, задачи, клиенты, стройка. Для вопроса про свободные квартиры используй apartments.free_total и apartments.by_status_counts; детали — apartments.available_sample.\n\n"
            . "Отвечай по-русски кратко, только факты из JSON. Не выдумывай цифры.\n\n"
            . "Фразу «{$refusal}» используй ТОЛЬКО для явного оффтопа (погода, политика, шутки, личное без связи с CRM). "
            . 'Не отказывай на вопросы про квартиры, деньги, задачи, клиентов, если в JSON есть поля.';

        $result = $this->completeWithCrmSnapshot($userMessage, $system, 0.25, 'OpenAI Telegram group agent');

        return $this->overrideTelegramRefusalWhenDataExists($userMessage, $result);
    }

    /**
     * Один короткий юмористический тезис про стройку / ремонт / стройплощадку (для почасовой рассылки в Telegram).
     *
     * @return array{content:string,usage?:array}
     */
    public function generateHourlyConstructionThesis(): array
    {
        $c = $this->resolveCredentials();
        $apiKey = $c['apiKey'];
        $model = $c['model'];
        $baseUrl = $c['baseUrl'];

        if ($apiKey === '') {
            return ['content' => ''];
        }

        $system = 'Ты — остроумный наблюдатель стройки. Ответь ОДНИМ коротким тезисом по-русски (1–3 предложения максимум). '
            .'Тема: строительство, ремонт, прорабы, сметы, сроки, краны, бетон, переносы, идеальные проекты и реальность. '
            .'Ирония и лёгкий юмор, без токсичности, без политики, без оскорблений групп людей, без мата. '
            .'Не используй markdown и кавычки-ёлочки. Каждый раз новая мысль, не повторяй штампы.';

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => 'Пиши один тезис.'],
        ];

        try {
            $response = Http::connectTimeout(15)
                ->timeout(60)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.88,
                    'max_tokens' => 220,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI hourly construction thesis failed', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 400),
                ]);

                return ['content' => ''];
            }

            $json = $response->json();
            $content = (string) ($json['choices'][0]['message']['content'] ?? '');
            $usage = $json['usage'] ?? null;

            if ($content === '') {
                return ['content' => '', 'usage' => is_array($usage) ? $usage : null];
            }

            return ['content' => trim($content), 'usage' => is_array($usage) ? $usage : null];
        } catch (\Throwable $e) {
            Log::error('OpenAI hourly construction thesis exception', ['message' => $e->getMessage()]);

            return ['content' => ''];
        }
    }

    /**
     * @param  array{content:string,usage?:array}  $result
     * @return array{content:string,usage?:array}
     */
    private function overrideTelegramRefusalWhenDataExists(string $userMessage, array $result): array
    {
        $content = trim($result['content'] ?? '');
        $refusal = TelegramGroupAssistantService::OFF_TOPIC_REPLY;
        $looksRefused = ($content === $refusal)
            || (mb_stripos($content, 'не входит в компетенции') !== false && mb_strlen($content) < 80);
        $modelEmptyReply = (bool) preg_match('/^ИИ вернул пустой ответ/u', $content);

        if (! $looksRefused && ! $modelEmptyReply) {
            return $result;
        }

        $snapshot = Cache::get('crm_ai_snapshot_v2');
        if (! is_array($snapshot)) {
            return $result;
        }

        if ($this->isFreeApartmentsCountQuestion($userMessage)) {
            $n = $snapshot['apartments']['free_total'] ?? null;
            if ($n !== null) {
                return [
                    'content' => 'По данным CRM сейчас свободных квартир: ' . (int) $n . '.',
                    'usage' => $result['usage'] ?? null,
                ];
            }
        }

        return $result;
    }

    private function isFreeApartmentsCountQuestion(string $q): bool
    {
        $q = mb_strtolower($q);
        if (! preg_match('/квартир/u', $q)) {
            return false;
        }
        if (! preg_match('/свобод|продаж|доступн|в\s+наличии|осталось|свободн/u', $q)) {
            return false;
        }

        return (bool) preg_match('/сколько|количеств|число|есть\s+ли|много\s+ли|как\s+много/u', $q);
    }

    /**
     * @return array{content:string,usage?:array}
     */
    private function completeWithCrmSnapshot(string $userQuestion, string $systemPrompt, float $temperature, string $logLabel): array
    {
        if (mb_strlen($userQuestion) > 4000) {
            $userQuestion = mb_substr($userQuestion, 0, 4000);
        }

        $c = $this->resolveCredentials();
        $apiKey = $c['apiKey'];
        $model = $c['model'];
        $baseUrl = $c['baseUrl'];

        if ($apiKey === '') {
            return ['content' => 'ИИ не настроен: в админке укажите API key для выбранного провайдера.'];
        }

        $snapshotTtl = max(5, (int) config('services.crm_snapshot_cache_ttl', 45));
        try {
            $t0 = microtime(true);
            $snapshot = Cache::remember('crm_ai_snapshot_v2', $snapshotTtl, static function () {
                return CrmDataSnapshotService::build();
            });
            $buildMs = (int) round((microtime(true) - $t0) * 1000);
            if ($buildMs > 2000) {
                Log::info('crm_snapshot_slow', ['ms' => $buildMs]);
            }
        } catch (\Throwable $e) {
            Log::error('CrmDataSnapshotService failed', ['message' => $e->getMessage()]);
            Cache::forget('crm_ai_snapshot_v2');

            return ['content' => 'Не удалось собрать данные из CRM для ответа.'];
        }

        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($snapshotJson === false) {
            return ['content' => 'Ошибка подготовки данных.'];
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => "CRM_DATA_JSON:\n{$snapshotJson}"],
            ['role' => 'user', 'content' => $userQuestion],
        ];

        try {
            $tHttp = microtime(true);
            $response = Http::connectTimeout(15)
                ->timeout(120)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => $temperature,
                ]);
            $httpMs = (int) round((microtime(true) - $tHttp) * 1000);
            if ($httpMs > 15000) {
                Log::info('openai_chat_completions_slow', ['ms' => $httpMs, 'label' => $logLabel]);
            }

            if (!$response->successful()) {
                $errorText = $this->extractErrorText($response->body());
                Log::warning($logLabel . ' failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'content' => 'Не удалось получить ответ ИИ (HTTP ' . $response->status() . ($errorText ? ' — ' . $errorText : '') . ').',
                ];
            }

            $json = $response->json();
            $content = (string) ($json['choices'][0]['message']['content'] ?? '');
            $usage = $json['usage'] ?? null;

            if ($content === '') {
                return ['content' => 'ИИ вернул пустой ответ.', 'usage' => is_array($usage) ? $usage : null];
            }

            return ['content' => trim($content), 'usage' => is_array($usage) ? $usage : null];
        } catch (\Throwable $e) {
            Log::error($logLabel . ' exception', ['message' => $e->getMessage()]);

            return ['content' => 'Ошибка соединения с ИИ.'];
        }
    }

    /**
     * @return array{content:string,usage?:array}
     */
    public function reply(AiConversation $conversation, array $context = []): array
    {
        $c = $this->resolveCredentials();
        $apiKey = $c['apiKey'];
        $model = $c['model'];
        $baseUrl = $c['baseUrl'];

        if ($apiKey === '') {
            return [
                'content' => 'AI API key is not configured.',
            ];
        }

        $systemPrompt = AiPrompt::where('is_active', true)->orderByDesc('id')->value('system_prompt');
        $systemPrompt = $systemPrompt ?: "Ты — ИИ помощник в CRM. Отвечай кратко и по делу на русском языке.";

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        $contextText = $this->formatContext($context);
        if ($contextText !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextText];
        }

        // Take last N messages to control token growth.
        $history = $conversation->messages()
            ->whereIn('role', [AiMessage::ROLE_USER, AiMessage::ROLE_ASSISTANT])
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        foreach ($history as $m) {
            $messages[] = [
                'role' => $m->role,
                'content' => $m->content,
            ];
        }

        try {
            $response = Http::connectTimeout(15)
                ->timeout(120)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.3,
                ]);

            if (!$response->successful()) {
                $errorText = $this->extractErrorText($response->body());
                Log::warning('OpenAI chat request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'content' => "Не удалось получить ответ от ИИ (ошибка провайдера: HTTP {$response->status()}" . ($errorText ? " — {$errorText}" : '') . ").",
                ];
            }

            $json = $response->json();
            $content = (string) ($json['choices'][0]['message']['content'] ?? '');
            $usage = $json['usage'] ?? null;

            if ($content === '') {
                return [
                    'content' => 'ИИ вернул пустой ответ.',
                    'usage' => is_array($usage) ? $usage : null,
                ];
            }

            return [
                'content' => $content,
                'usage' => is_array($usage) ? $usage : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI chat request exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'content' => 'Не удалось получить ответ от ИИ (ошибка соединения).',
            ];
        }
    }

    /**
     * Режим совещания: активный промпт + инструкции модератора + справочник сотрудников.
     *
     * @return array{content:string,usage?:array}
     */
    public function replyMeeting(AiConversation $conversation, array $context = []): array
    {
        $c = $this->resolveCredentials();
        $apiKey = $c['apiKey'];
        $model = $c['model'];
        $baseUrl = $c['baseUrl'];

        if ($apiKey === '') {
            return [
                'content' => 'AI API key is not configured.',
            ];
        }

        $systemPrompt = AiPrompt::where('is_active', true)->orderByDesc('id')->value('system_prompt');
        $systemPrompt = $systemPrompt ?: 'Ты помогаешь вести совещание в CRM.';

        $staff = User::query()->orderBy('name')->get(['id', 'name', 'email'])->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ])->values()->all();

        $staffJson = json_encode($staff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $systemPrompt .= "\n\n---\n".MeetingAiService::facilitatorInstructions();
        $systemPrompt .= "\n\nСправочник сотрудников (назначай ответственных только по id из этого JSON):\n".$staffJson;

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        $contextText = $this->formatContext($context);
        if ($contextText !== null) {
            $messages[] = ['role' => 'system', 'content' => $contextText];
        }

        $history = $conversation->messages()
            ->whereIn('role', [AiMessage::ROLE_USER, AiMessage::ROLE_ASSISTANT])
            ->orderByDesc('id')
            ->limit(34)
            ->get()
            ->reverse()
            ->values();

        foreach ($history as $m) {
            $messages[] = [
                'role' => $m->role,
                'content' => $m->content,
            ];
        }

        try {
            $response = Http::connectTimeout(15)
                ->timeout(120)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.45,
                    'max_tokens' => 2500,
                ]);

            if (! $response->successful()) {
                $errorText = $this->extractErrorText($response->body());
                Log::warning('OpenAI meeting chat request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'content' => "Не удалось получить ответ от ИИ (ошибка провайдера: HTTP {$response->status()}" . ($errorText ? " — {$errorText}" : '') . ').',
                ];
            }

            $json = $response->json();
            $content = (string) ($json['choices'][0]['message']['content'] ?? '');
            $usage = $json['usage'] ?? null;

            if ($content === '') {
                return [
                    'content' => 'ИИ вернул пустой ответ.',
                    'usage' => is_array($usage) ? $usage : null,
                ];
            }

            return [
                'content' => $content,
                'usage' => is_array($usage) ? $usage : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI meeting chat exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'content' => 'Не удалось получить ответ от ИИ (ошибка соединения).',
            ];
        }
    }

    /**
     * Извлечь из стенограммы JSON-массив задач для создания в CRM.
     *
     * @return array{ok:bool, items?:array<int,array<string,mixed>>, error?:string}
     */
    public function extractMeetingTasksJson(string $transcript): array
    {
        $c = $this->resolveCredentials();
        $apiKey = $c['apiKey'];
        $model = $c['model'];
        $baseUrl = $c['baseUrl'];

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Не настроен API key ИИ.'];
        }

        $staff = User::query()->orderBy('name')->get(['id', 'name'])->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->values()->all();

        $staffJson = json_encode($staff, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $user = "Справочник сотрудников (только эти id для responsible_user_id):\n{$staffJson}\n\nСтенограмма совещания:\n\n".$transcript;

        $system = 'Ты извлекаешь итоговый список задач из стенограммы совещания. '
            .'Верни ТОЛЬКО валидный JSON-массив без markdown и без текста до/после. Формат элемента: '
            .'{"title":"строка","description":null или строка,"responsible_user_id":число или null,"due_date":"Y-m-d" или null,'
            .'"status":"in_development"|"processing"|"execution"|"completed","client_id":число или null}. '
            .'Если задач нет — верни [].';

        try {
            $response = Http::connectTimeout(15)
                ->timeout(90)
                ->withToken($apiKey)
                ->acceptJson()
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 4000,
                ]);

            if (! $response->successful()) {
                return ['ok' => false, 'error' => 'ИИ недоступен (HTTP '.$response->status().').'];
            }

            $raw = (string) ($response->json('choices.0.message.content') ?? '');
            $raw = $this->stripJsonFence($raw);
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return ['ok' => false, 'error' => 'Не удалось разобрать JSON задач.'];
            }

            return ['ok' => true, 'items' => $decoded];
        } catch (\Throwable $e) {
            Log::error('extractMeetingTasksJson', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Ошибка при обращении к ИИ.'];
        }
    }

    private function stripJsonFence(string $raw): string
    {
        $raw = trim($raw);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $raw, $m)) {
            return trim($m[1]);
        }

        return $raw;
    }

    private function formatContext(array $context): ?string
    {
        $filtered = array_filter($context, fn ($v) => $v !== null && $v !== [] && $v !== '');
        if ($filtered === []) {
            return null;
        }

        $json = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! $json) {
            return null;
        }

        $intro = 'Ты — аналитик CRM. Ниже JSON с актуальными данными (только чтение). Отвечай по-русски по фактам из данных: ФИО покупателей, суммы и площади, списки квартир, транзакции, задачи. '
            .'Используй поля `apartments.sold` (buyers_fio, living_area_total_m2, sold_apartments_sample), `balance_transactions.recent`, `clients`, `tasks`. '
            .'Если нужного поля в снимке нет — так и скажи. Не выдумывай цифры.';

        return $intro."\n\nCONTEXT (readonly CRM data):\n".$json;
    }

    private function normalizeBaseUrl(string $raw, string $provider): string
    {
        $raw = trim($raw);
        $default = $provider === 'deepseek' ? 'https://api.deepseek.com/v1' : 'https://api.openai.com/v1';
        $url = rtrim($raw !== '' ? $raw : $default, '/');

        // Accept either https://host or https://host/v1
        if (!str_ends_with($url, '/v1') && !str_contains($url, '/v1/')) {
            $url .= '/v1';
        }

        return $url;
    }

    private function extractErrorText(string $body): ?string
    {
        $body = trim($body);
        if ($body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }

        $msg = $json['error']['message'] ?? null;
        if (!is_string($msg) || $msg === '') {
            return null;
        }

        // keep it short for UI
        $msg = mb_substr($msg, 0, 180);
        return $msg;
    }
}

