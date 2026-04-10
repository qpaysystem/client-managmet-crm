<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPrompt;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiChatService
{
    /**
     * @return array{content:string,usage?:array}
     */
    public function reply(AiConversation $conversation, array $context = []): array
    {
        $apiKey = (string) Setting::get('openai_api_key', config('services.openai.api_key'));
        $model = (string) Setting::get('openai_model', config('services.openai.model'));
        $baseUrlRaw = (string) Setting::get('openai_base_url', config('services.openai.base_url', 'https://api.openai.com/v1'));
        $baseUrl = $this->normalizeBaseUrl($baseUrlRaw);

        if ($apiKey === '') {
            return [
                'content' => 'OpenAI API key is not configured.',
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
            $response = Http::timeout(30)
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

    private function formatContext(array $context): ?string
    {
        $filtered = array_filter($context, fn ($v) => $v !== null && $v !== [] && $v !== '');
        if ($filtered === []) {
            return null;
        }

        $json = json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!$json) {
            return null;
        }

        return "CONTEXT (readonly CRM data):\n{$json}";
    }

    private function normalizeBaseUrl(string $raw): string
    {
        $raw = trim($raw);
        $url = rtrim($raw !== '' ? $raw : 'https://api.openai.com/v1', '/');

        // Accept either https://api.openai.com or https://api.openai.com/v1
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

