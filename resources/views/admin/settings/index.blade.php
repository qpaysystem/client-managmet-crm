@extends('layouts.admin')
@section('title', 'Настройки')
@section('content')
<h1 class="h4 mb-4">Системные настройки</h1>
<form method="post" action="{{ route('admin.settings.store') }}">
    @csrf
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Общие</h5>
            <div class="mb-3">
                <label class="form-label">Валюта для баланса</label>
                <input type="text" name="currency" class="form-control" value="{{ $settings['currency'] }}" maxlength="10" placeholder="RUB, USD, EUR">
            </div>
            <div class="mb-3">
                <label class="form-label">Максимальный размер загружаемого файла (МБ)</label>
                <input type="number" name="max_upload_mb" class="form-control" value="{{ $settings['max_upload_mb'] }}" min="1" max="50">
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="mail_notifications" value="1" class="form-check-input" id="mail_notifications" {{ ($settings['mail_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="mail_notifications">Включить почтовые уведомления</label>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Telegram-бот</h5>
            <p class="text-muted small">Уведомления о проведении транзакций. Создайте бота через <a href="https://t.me/BotFather" target="_blank">@BotFather</a>, получите токен и chat_id.</p>
            <div class="mb-3">
                <label class="form-label">Токен бота</label>
                <input type="text" name="telegram_bot_token" class="form-control" value="{{ $settings['telegram_bot_token'] ?? '' }}" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
            </div>
            <div class="mb-3">
                <label class="form-label">Username бота (без @)</label>
                <input type="text" name="telegram_bot_username" class="form-control" value="{{ $settings['telegram_bot_username'] ?: 'NskCapital_bot' }}" placeholder="NskCapital_bot">
                <small class="text-muted">Нужен для входа клиентов в личный кабинет</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Chat ID</label>
                <input type="text" name="telegram_chat_id" class="form-control" value="{{ $settings['telegram_chat_id'] ?? '' }}" placeholder="123456789 или -1001234567890">
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="telegram_notify_transactions" value="1" class="form-check-input" id="telegram_notify" {{ ($settings['telegram_notify_transactions'] ?? '0') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="telegram_notify">Отправлять уведомления о транзакциях</label>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="telegram_notify_tasks" value="1" class="form-check-input" id="telegram_notify_tasks" {{ ($settings['telegram_notify_tasks'] ?? '0') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="telegram_notify_tasks">Уведомления об изменениях в задачах (создание, изменение, удаление)</label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="telegram_notify_stages" value="1" class="form-check-input" id="telegram_notify_stages" {{ ($settings['telegram_notify_stages'] ?? '0') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="telegram_notify_stages">Уведомления об изменениях в этапах строительства (создание, изменение, удаление)</label>
            </div>
            <hr class="my-3">
            <p class="text-muted small mb-2">Входящие сообщения группы (лог переписки + ответ на фразу про «текущую информацию»): укажите URL webhook в BotFather и при необходимости секрет.</p>
            <div class="mb-2">
                <span class="form-label d-block">Webhook</span>
                <code class="small user-select-all">{{ url('/telegram/webhook') }}</code>
            </div>
            <div class="mb-2">
                <label class="form-label">Секрет webhook (опционально)</label>
                <input type="password" name="telegram_webhook_secret" class="form-control" value="" autocomplete="new-password" placeholder="{{ !empty($settings['telegram_webhook_secret'] ?? '') ? 'оставьте пустым, чтобы не менять' : 'случайная строка' }}">
                <small class="text-muted">При <code>setWebhook</code> укажите тот же <code>secret_token</code> или добавьте к URL <code>?secret=...</code>. @if(!empty($settings['telegram_webhook_secret'] ?? ''))<span class="text-success">Секрет сохранён.</span>@endif</small>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="telegram_webhook_secret_clear" value="1" class="form-check-input" id="telegram_webhook_secret_clear">
                <label class="form-check-label" for="telegram_webhook_secret_clear">Сбросить секрет webhook</label>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="telegram_group_assistant_reply" value="1" class="form-check-input" id="telegram_group_assistant_reply" {{ ($settings['telegram_group_assistant_reply'] ?? '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="telegram_group_assistant_reply">Отвечать в группе на запрос актуальной информации («помоги получить текущую информацию» и похожее)</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="telegram_group_ai_crm" value="1" class="form-check-input" id="telegram_group_ai_crm" {{ ($settings['telegram_group_ai_crm'] ?? '1') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="telegram_group_ai_crm">ИИ по данным CRM в группе: команды <code>/вопрос …</code> и <code>/ask …</code>, а также короткие вопросы про квартиры, транзакции, задачи (нужен API key ИИ)</label>
            </div>
            <p class="text-muted small mt-2 mb-0">Бот должен быть в группе; в BotFather для бота отключите режим приватности (<code>/setprivacy</code> → Disable), иначе бот не увидит обычные сообщения. Chat ID выше должен совпадать с этой группой.</p>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">ИИ помощник</h5>
            <p class="text-muted small mb-3">Настройки провайдера для вкладки «ИИ помощник». Поле «API key» оставьте пустым, если не хотите менять текущий ключ.</p>
            <div class="mb-3">
                <label class="form-label">Провайдер</label>
                <select name="ai_provider" class="form-select">
                    <option value="openai" @selected(($settings['ai_provider'] ?? 'openai') === 'openai')>OpenAI</option>
                    <option value="deepseek" @selected(($settings['ai_provider'] ?? 'openai') === 'deepseek')>DeepSeek</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">API key</label>
                <input type="password" name="ai_api_key" class="form-control" value="" placeholder="sk-...">
            </div>
            <div class="mb-3">
                <label class="form-label">Model</label>
                <input type="text" name="ai_model" class="form-control" value="{{ $settings['ai_model'] ?? '' }}" placeholder="gpt-4.1-mini / deepseek-chat">
            </div>
            <div class="mb-3">
                <label class="form-label">Base URL</label>
                <input type="text" name="ai_base_url" class="form-control" value="{{ $settings['ai_base_url'] ?? '' }}" placeholder="https://api.openai.com/v1 / https://api.deepseek.com/v1">
                <div class="form-text">Нужно только если используешь прокси/свой gateway. Можно без `/v1`.</div>
            </div>
            <div class="form-check">
                <input type="checkbox" name="ai_include_crm_snapshot" value="1" class="form-check-input" id="ai_include_crm_snapshot" {{ ($settings['ai_include_crm_snapshot'] ?? '0') == '1' ? 'checked' : '' }}>
                <label class="form-check-label" for="ai_include_crm_snapshot">В чате «ИИ помощник» в админке подмешивать снимок данных CRM (квартиры, последние транзакции, задачи) — больше токенов на запрос</label>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
</form>
@endsection
