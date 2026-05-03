@extends('layouts.admin')
@section('title', 'Настройки')
@section('content')
<h1 class="h4 mb-3">Системные настройки</h1>
<p class="text-muted small mb-3">
    Разделы: общие параметры и почта, Telegram, LLM (OpenAI / DeepSeek). ИИ-чат и Telegram-агент берут ключ и URL из вкладки выбранного <span class="fw-semibold">активного провайдера</span> ниже; на второй подвкладке LLM можно заранее сохранить и второй провайдер.
</p>

<form method="post" action="{{ route('admin.settings.store') }}">
    @csrf

    <ul class="nav nav-tabs" id="settingsMainTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="true">Общее</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-tg-btn" data-bs-toggle="tab" data-bs-target="#tab-tg" type="button" role="tab" aria-controls="tab-tg" aria-selected="false">Telegram</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-llm-btn" data-bs-toggle="tab" data-bs-target="#tab-llm" type="button" role="tab" aria-controls="tab-llm" aria-selected="false">LLM</button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-3 mb-3 bg-body" id="settingsMainTabContent">
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-btn" tabindex="0">
            <div class="card border-0 shadow-sm mb-3">
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
                    <div class="form-check mb-0">
                        <input type="checkbox" name="mail_notifications" value="1" class="form-check-input" id="mail_notifications" {{ ($settings['mail_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="mail_notifications">Включить почтовые уведомления</label>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Почта (Яндекс / SMTP)</h5>
                    <p class="text-muted small mb-2">
                        Параметры SMTP задаются в <code>.env</code> на сервере (<code>MAIL_*</code>), затем <code>php artisan config:clear</code>.
                    </p>
                    @if(config('mail.mailers.smtp.username'))
                        <p class="small text-success mb-2">
                            SMTP: <code>{{ config('mail.mailers.smtp.host') }}</code>, порт {{ config('mail.mailers.smtp.port') }},
                            пользователь: <code>{{ config('mail.mailers.smtp.username') }}</code>
                        </p>
                    @else
                        <p class="small text-warning mb-2">В <code>.env</code> не задан <code>MAIL_USERNAME</code> — отправка не настроена.</p>
                    @endif
                    <details class="mb-3">
                        <summary class="small text-muted" style="cursor:pointer;">Пример для Яндекс Почты</summary>
                        <pre class="small bg-light border rounded p-2 mt-2 mb-0">MAIL_MAILER=smtp
MAIL_HOST=smtp.yandex.ru
MAIL_PORT=465
MAIL_USERNAME=ваш_ящик@yandex.ru
MAIL_PASSWORD=пароль_приложения_из_Яндекс_ID
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="${MAIL_USERNAME}"
MAIL_FROM_NAME="${APP_NAME}"</pre>
                    </details>
                    @if($errors->has('mail_test'))
                        <div class="alert alert-danger small">{{ $errors->first('mail_test') }}</div>
                    @endif
                    @if(session('mail_test_success'))
                        <div class="alert alert-success small">{{ session('mail_test_success') }}</div>
                    @endif
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label small mb-1">Отправить тестовое письмо на адрес</label>
                            <input type="email" name="test_email" form="mailTestForm" class="form-control" value="{{ old('test_email', auth()->user()->email ?? '') }}" placeholder="email@example.com" required maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" form="mailTestForm" class="btn btn-outline-primary w-100">Проверить отправку</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-tg" role="tabpanel" aria-labelledby="tab-tg-btn" tabindex="0">
            <div class="card border-0 shadow-sm">
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
                    <hr class="my-3">
                    <h6 class="mb-2">Группа «управление проектом Элитный»</h6>
                    <p class="text-muted small mb-2">Вторая группа: CRM сохраняет все сообщения и (опционально) запускает ИИ-анализ для автосоздания событий/задач по контексту проекта.</p>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Chat ID группы «Элитный»</label>
                            <input type="text" name="telegram_elite_chat_id" class="form-control" value="{{ $settings['telegram_elite_chat_id'] ?? '' }}" placeholder="-1001234567890">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Проект в CRM</label>
                            <select name="telegram_elite_project_id" class="form-select">
                                <option value="">—</option>
                                @foreach($projects ?? [] as $p)
                                    <option value="{{ $p->id }}" @selected((string) ($settings['telegram_elite_project_id'] ?? '') === (string) $p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cooldown (сек)</label>
                            <input type="number" name="telegram_elite_ai_cooldown_seconds" class="form-control" value="{{ $settings['telegram_elite_ai_cooldown_seconds'] ?? '' }}" min="0" max="86400" placeholder="0">
                        </div>
                    </div>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="telegram_elite_ai_events" value="1" class="form-check-input" id="telegram_elite_ai_events" {{ ($settings['telegram_elite_ai_events'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="telegram_elite_ai_events">ИИ-анализировать сообщения и автоматически создавать события/задачи</label>
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
                    <div class="form-check mb-3">
                        <input type="checkbox" name="telegram_hourly_construction_thesis" value="1" class="form-check-input" id="telegram_hourly_construction_thesis" {{ ($settings['telegram_hourly_construction_thesis'] ?? '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="telegram_hourly_construction_thesis">Раз в час — один смешной «тезис про стройку» от ИИ в этот чат (нужны токен, chat_id и API key ИИ; срабатывает через cron <code>schedule:run</code>)</label>
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
                        <input type="checkbox" name="telegram_group_ai_all" value="1" class="form-check-input" id="telegram_group_ai_all" {{ ($settings['telegram_group_ai_all'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="telegram_group_ai_all"><span class="fw-semibold">Все сообщения группы через ИИ-агента</span> — снимок данных CRM в каждом запросе; вопрос не по теме → ответ: «{{ \App\Services\TelegramGroupAssistantService::OFF_TOPIC_REPLY }}» (нужны токен бота и API key ИИ)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="telegram_group_assistant_reply" value="1" class="form-check-input" id="telegram_group_assistant_reply" {{ ($settings['telegram_group_assistant_reply'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="telegram_group_assistant_reply">Отвечать на фразу «помоги получить текущую информацию» (короткий ответ; не используется, если включено «все сообщения через ИИ»)</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="telegram_group_ai_crm" value="1" class="form-check-input" id="telegram_group_ai_crm" {{ ($settings['telegram_group_ai_crm'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="telegram_group_ai_crm">Режим только <code>/вопрос</code> / <code>/ask</code> и узкие вопросы по CRM (если «все сообщения через ИИ» выключено)</label>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Бот должен быть в группе; в BotFather для бота отключите режим приватности (<code>/setprivacy</code> → Disable), иначе бот не увидит обычные сообщения. Chat ID выше должен совпадать с этой группой.</p>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-llm" role="tabpanel" aria-labelledby="tab-llm-btn" tabindex="0">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title">ИИ помощник и интеграции</h5>
                    <p class="text-muted small mb-3">
                        Ключи и base URL для <span class="fw-semibold">OpenAI</span> и <span class="fw-semibold">DeepSeek</span> задаются на подвкладках ниже (сохраняются в БД настроек).
                        Активный провайдер определяет, какой набор используется в админ-чате «ИИ помощник», вопросах по CRM и (если не переопределено) в Telegram.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Активный провайдер</label>
                            <select name="ai_provider" class="form-select">
                                <option value="openai" @selected(($settings['ai_provider'] ?? 'openai') === 'openai')>OpenAI</option>
                                <option value="deepseek" @selected(($settings['ai_provider'] ?? 'openai') === 'deepseek')>DeepSeek</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check mb-0">
                                <input type="checkbox" name="ai_include_crm_snapshot" value="1" class="form-check-input" id="ai_include_crm_snapshot" {{ ($settings['ai_include_crm_snapshot'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ai_include_crm_snapshot">В чате «ИИ помощник» подмешивать расширенный снимок CRM (больше токенов на запрос)</label>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-pills mt-4 mb-3" id="llmSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="llm-openai-tab" data-bs-toggle="pill" data-bs-target="#llm-pane-openai" type="button" role="tab" aria-controls="llm-pane-openai" aria-selected="true">OpenAI</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="llm-deepseek-tab" data-bs-toggle="pill" data-bs-target="#llm-pane-deepseek" type="button" role="tab" aria-controls="llm-pane-deepseek" aria-selected="false">DeepSeek</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="llmSubTabContent">
                        <div class="tab-pane fade show active" id="llm-pane-openai" role="tabpanel" aria-labelledby="llm-openai-tab" tabindex="0">
                            <p class="small text-muted mb-3">Используется при активном провайдере OpenAI и как запасной ключ для режимов с <code>openai</code>.</p>
                            @if(!empty($settings['openai_api_key_saved']))
                                <p class="small text-success mb-2">API key OpenAI уже сохранён в базе. Введите новый только чтобы заменить.</p>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">API key</label>
                                <input type="password" name="openai_api_key" class="form-control" value="" autocomplete="new-password" placeholder="sk-...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="openai_base_url" class="form-control" value="{{ $settings['openai_base_url'] ?? '' }}" placeholder="https://api.openai.com/v1">
                                <div class="form-text">Можно без суффикса <code>/v1</code> — нормализуется автоматически.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Model</label>
                                <input type="text" name="openai_model" class="form-control" value="{{ $settings['openai_model'] ?? '' }}" placeholder="gpt-4.1-mini">
                            </div>
                        </div>
                        <div class="tab-pane fade" id="llm-pane-deepseek" role="tabpanel" aria-labelledby="llm-deepseek-tab" tabindex="0">
                            <p class="small text-muted mb-3">Используется при активном провайдере DeepSeek и как запасной ключ для режимов с <code>deepseek</code>.</p>
                            @if(!empty($settings['deepseek_api_key_saved']))
                                <p class="small text-success mb-2">API key DeepSeek уже сохранён в базе. Введите новый только чтобы заменить.</p>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">API key</label>
                                <input type="password" name="deepseek_api_key" class="form-control" value="" autocomplete="new-password" placeholder="sk-...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" name="deepseek_base_url" class="form-control" value="{{ $settings['deepseek_base_url'] ?? '' }}" placeholder="https://api.deepseek.com/v1">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Model</label>
                                <input type="text" name="deepseek_model" class="form-control" value="{{ $settings['deepseek_model'] ?? '' }}" placeholder="deepseek-chat">
                            </div>
                        </div>
                    </div>

                    <details class="mt-4">
                        <summary class="small text-muted" style="cursor:pointer;">Дополнительно: общий API key и URL (устаревшие поля <code>ai_*</code>)</summary>
                        <p class="small text-muted mt-2 mb-2">Если на подвкладках выше не заданы ключ/model/base, подставятся эти значения. Пустой API key — не перезаписывать сохранённый.</p>
                        <div class="mb-2">
                            <label class="form-label small">Общий API key (<code>ai_api_key</code>)</label>
                            <input type="password" name="ai_api_key" class="form-control form-control-sm" value="" autocomplete="new-password" placeholder="(не менять)">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Model (<code>ai_model</code>)</label>
                            <input type="text" name="ai_model" class="form-control form-control-sm" value="{{ $settings['ai_model'] ?? '' }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small">Base URL (<code>ai_base_url</code>)</label>
                            <input type="text" name="ai_base_url" class="form-control form-control-sm" value="{{ $settings['ai_base_url'] ?? '' }}">
                        </div>
                    </details>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">ИИ для Telegram «Элитный»: текст / медиа</h5>
                    <p class="text-muted small mb-2">Раздельно: например текст через DeepSeek, вложения через OpenAI.</p>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Text провайдер</label>
                            <select name="ai_text_provider" class="form-select">
                                <option value="" @selected(($settings['ai_text_provider'] ?? '') === '')>— (как общий)</option>
                                <option value="deepseek" @selected(($settings['ai_text_provider'] ?? '') === 'deepseek')>DeepSeek</option>
                                <option value="openai" @selected(($settings['ai_text_provider'] ?? '') === 'openai')>OpenAI</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Text model</label>
                            <input type="text" name="ai_text_model" class="form-control" value="{{ $settings['ai_text_model'] ?? '' }}" placeholder="deepseek-chat / gpt-4.1-mini">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Text base URL</label>
                            <input type="text" name="ai_text_base_url" class="form-control" value="{{ $settings['ai_text_base_url'] ?? '' }}" placeholder="https://api.deepseek.com/v1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Text API key</label>
                            <input type="password" name="ai_text_api_key" class="form-control" value="" placeholder="(не менять)">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Media провайдер</label>
                            <select name="ai_media_provider" class="form-select">
                                <option value="" @selected(($settings['ai_media_provider'] ?? '') === '')>— (как общий)</option>
                                <option value="openai" @selected(($settings['ai_media_provider'] ?? '') === 'openai')>OpenAI</option>
                                <option value="deepseek" @selected(($settings['ai_media_provider'] ?? '') === 'deepseek')>DeepSeek</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Media model</label>
                            <input type="text" name="ai_media_model" class="form-control" value="{{ $settings['ai_media_model'] ?? '' }}" placeholder="gpt-4o-mini">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Media base URL</label>
                            <input type="text" name="ai_media_base_url" class="form-control" value="{{ $settings['ai_media_base_url'] ?? '' }}" placeholder="https://api.openai.com/v1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Media API key</label>
                            <input type="password" name="ai_media_api_key" class="form-control" value="" placeholder="(не менять)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
</form>

<form id="mailTestForm" method="post" action="{{ route('admin.settings.mail-test') }}" class="d-none">
    @csrf
</form>
@endsection
