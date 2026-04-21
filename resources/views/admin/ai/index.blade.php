@extends('layouts.admin')
@section('title', 'ИИ помощник')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">ИИ помощник</h1>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-prompts" data-bs-toggle="tab" data-bs-target="#pane-prompts" type="button" role="tab">Промпт</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-events" data-bs-toggle="tab" data-bs-target="#pane-events" type="button" role="tab">События компании</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-chat" data-bs-toggle="tab" data-bs-target="#pane-chat" type="button" role="tab">Чат</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-telegram" data-bs-toggle="tab" data-bs-target="#pane-telegram" type="button" role="tab"><i class="bi bi-telegram"></i> Сообщения Telegram</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-prompts" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Сохранённые промпты</strong>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-new-prompt"><i class="bi bi-plus-lg"></i> Новый</button>
                    </div>
                    <div class="list-group list-group-flush" id="prompt-list">
                        @forelse($prompts as $p)
                            <button type="button"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center prompt-item"
                                    data-prompt-id="{{ $p->id }}"
                                    data-prompt-title="{{ e($p->title) }}"
                                    data-prompt-system="{{ e($p->system_prompt) }}">
                                <span class="text-truncate" style="max-width: 280px;">
                                    {{ $p->title }}
                                    @if($p->is_active) <span class="badge bg-success ms-2">Активный</span> @endif
                                </span>
                                <span class="text-muted small">#{{ $p->id }}</span>
                            </button>
                        @empty
                            <div class="p-3 text-muted">Промптов ещё нет.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><strong>Редактор промпта</strong></div>
                    <div class="card-body">
                        <form id="prompt-form">
                            <input type="hidden" id="prompt-id" value="">
                            <div class="mb-3">
                                <label class="form-label">Название</label>
                                <input type="text" class="form-control" id="prompt-title" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">System prompt</label>
                                <textarea class="form-control" id="prompt-system" rows="10" required></textarea>
                                <div class="form-text">Используется как system-инструкция для модели.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="btn-save-prompt">Сохранить</button>
                                <button type="button" class="btn btn-outline-success" id="btn-activate-prompt" disabled>Сделать активным</button>
                                <button type="button" class="btn btn-outline-secondary ms-auto" id="btn-clear-prompt">Очистить</button>
                            </div>
                        </form>
                        <div class="alert alert-danger mt-3 d-none" id="prompt-error"></div>
                        <div class="alert alert-success mt-3 d-none" id="prompt-success"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-events" role="tabpanel">
        <div class="card mb-3">
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Фиксируйте значимые события (факты, решения, изменения), которые влияют на общую картину предприятия.
                    Они автоматически попадают в контекст ИИ при чате и при режиме «Совещание» (вместе с промптом и данными CRM).
                </p>
                <form id="company-event-form">
                    <input type="hidden" id="company-event-id" value="">
                    <label class="form-label">Описание события</label>
                    <textarea class="form-control" id="company-event-description" rows="8" maxlength="50000" placeholder="Сформулируйте, что произошло и почему это важно для компании…" required></textarea>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-primary" id="btn-company-event-save">Сохранить</button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-company-event-new">Новое событие</button>
                    </div>
                </form>
                <div class="alert alert-danger mt-3 d-none" id="company-event-error"></div>
                <div class="alert alert-success mt-3 d-none" id="company-event-success"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><strong>Журнал событий</strong> <span class="text-muted small">(новые сверху)</span></div>
            <div class="list-group list-group-flush" id="company-event-list">
                @forelse($companyEvents as $ev)
                    <div class="list-group-item company-event-item py-3"
                         data-event-id="{{ $ev->id }}">
                        <div class="d-flex justify-content-between gap-2 flex-wrap">
                            <span class="text-muted small">#{{ $ev->id }} · {{ $ev->created_at?->format('d.m.Y H:i') }}</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-company-event-edit">Редактировать</button>
                                <button type="button" class="btn btn-outline-danger btn-company-event-delete">Удалить</button>
                            </div>
                        </div>
                        <div class="mt-2 text-break" style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($ev->description, 400) }}</div>
                    </div>
                @empty
                    <div class="p-3 text-muted" id="company-event-empty">Событий пока нет.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-chat" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <strong>Диалоги</strong>
                        <div class="d-flex flex-wrap gap-1 align-items-center">
                            <input type="datetime-local" class="form-control form-control-sm" id="meeting-at" title="Дата и время совещания (необязательно)" style="max-width: 11rem;">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-new-meeting" title="Режим совещания: повестка, вопросы, задачи в CRM"><i class="bi bi-people"></i> Совещание</button>
                            <button class="btn btn-sm btn-primary" id="btn-new-conv"><i class="bi bi-plus-lg"></i> Новый</button>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="conv-list">
                        @forelse($conversations as $c)
                            <button type="button" class="list-group-item list-group-item-action conv-item"
                                    data-conv-id="{{ $c->id }}"
                                    data-conv-kind="{{ $c->kind ?? 'general' }}"
                                    data-conv-title="{{ e($c->title ?? ('Диалог #' . $c->id)) }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-truncate">{{ $c->title ?? ('Диалог #' . $c->id) }}</span>
                                    <span class="text-muted small text-nowrap ms-1">#{{ $c->id }} @if(($c->kind ?? 'general') === 'meeting')<span class="badge bg-info">совещ.</span>@endif</span>
                                </div>
                            </button>
                        @empty
                            <div class="p-3 text-muted">Диалогов ещё нет.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header"><strong>Контекст (readonly)</strong></div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Поиск</label>
                            <input type="text" class="form-control form-control-sm" id="ctx-q" placeholder="ФИО клиента / проект / задача">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Клиент</label>
                            <select class="form-select form-select-sm" id="ctx-client"></select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Проект</label>
                            <select class="form-select form-select-sm" id="ctx-project"></select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small mb-1">Задача</label>
                            <select class="form-select form-select-sm" id="ctx-task"></select>
                        </div>
                        <div class="form-text mt-2">Выбранный контекст добавляется в system-сообщение при запросе.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong id="chat-title">Чат</strong>
                        <span class="text-muted small" id="chat-conv-id"></span>
                    </div>
                    <div class="card-body" style="height: 420px; overflow:auto;" id="chat-messages">
                        <div class="text-muted">Выберите диалог слева или создайте новый.</div>
                    </div>
                    <div class="card-footer">
                        <div id="meeting-apply-bar" class="alert alert-light border py-2 mb-2 d-none small">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>Когда список задач согласован, внесите его в CRM:</span>
                                <button type="button" class="btn btn-sm btn-success" id="btn-meeting-apply" disabled>Создать задачи в CRM</button>
                            </div>
                            <div class="text-muted mt-1">Или напишите в чат: «подтверждаю создание задач в CRM».</div>
                        </div>
                        <form id="chat-form" class="d-flex gap-2">
                            <input type="text" class="form-control" id="chat-input" placeholder="Сообщение..." maxlength="10000" autocomplete="off">
                            <button class="btn btn-primary" type="submit" id="chat-send" disabled>Отправить</button>
                        </form>
                        <div class="alert alert-danger mt-2 d-none" id="chat-error"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pane-telegram" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>Сообщения группы</strong>
                    <span class="text-muted small ms-2">дубль переписки из БД (webhook)</span>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <select class="form-select form-select-sm" id="tg-chat-select" style="max-width: 320px;">
                        <option value="{{ $telegramChatId ?? '' }}">Основная группа</option>
                        @if(($telegramEliteChatId ?? '') !== '')
                            <option value="{{ $telegramEliteChatId }}">Элитный (управление проектом)</option>
                        @endif
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-tg-refresh" title="Обновить">
                        <i class="bi bi-arrow-clockwise"></i> Обновить
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2" id="tg-meta-hint">
                    @if($telegramChatId ?? '')
                        Chat ID в настройках: <code>{{ $telegramChatId }}</code>
                    @else
                        Задайте Chat ID в настройках — сюда попадут сообщения той же группы, куда идут уведомления.
                    @endif
                </p>
                <div id="tg-chat" class="border rounded p-3 bg-light" style="height: 480px; overflow: auto; font-size: 0.95rem;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ----- Prompts -----
    const promptList = document.getElementById('prompt-list');
    const promptIdEl = document.getElementById('prompt-id');
    const promptTitleEl = document.getElementById('prompt-title');
    const promptSystemEl = document.getElementById('prompt-system');
    const promptForm = document.getElementById('prompt-form');
    const btnNewPrompt = document.getElementById('btn-new-prompt');
    const btnActivatePrompt = document.getElementById('btn-activate-prompt');
    const btnClearPrompt = document.getElementById('btn-clear-prompt');
    const promptError = document.getElementById('prompt-error');
    const promptSuccess = document.getElementById('prompt-success');

    function showPromptError(msg) {
        promptError.textContent = msg || 'Ошибка';
        promptError.classList.remove('d-none');
        promptSuccess.classList.add('d-none');
    }
    function showPromptSuccess(msg) {
        promptSuccess.textContent = msg || 'OK';
        promptSuccess.classList.remove('d-none');
        promptError.classList.add('d-none');
        setTimeout(() => promptSuccess.classList.add('d-none'), 1500);
    }
    function clearPromptForm() {
        promptIdEl.value = '';
        promptTitleEl.value = '';
        promptSystemEl.value = '';
        btnActivatePrompt.disabled = true;
    }
    function pickPromptFromButton(btn) {
        promptIdEl.value = btn.dataset.promptId || '';
        promptTitleEl.value = btn.dataset.promptTitle || '';
        promptSystemEl.value = btn.dataset.promptSystem || '';
        btnActivatePrompt.disabled = !promptIdEl.value;
    }
    if (promptList) {
        promptList.addEventListener('click', (e) => {
            const btn = e.target.closest('.prompt-item');
            if (!btn) return;
            pickPromptFromButton(btn);
        });
    }
    btnNewPrompt && btnNewPrompt.addEventListener('click', clearPromptForm);
    btnClearPrompt && btnClearPrompt.addEventListener('click', clearPromptForm);

    promptForm && promptForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        promptError.classList.add('d-none');
        promptSuccess.classList.add('d-none');

        const id = promptIdEl.value;
        const payload = {
            title: promptTitleEl.value.trim(),
            system_prompt: promptSystemEl.value,
        };
        try {
            const url = id
                ? `{{ route('admin.ai.prompts.update', ['prompt' => '__ID__']) }}`.replace('__ID__', id)
                : `{{ route('admin.ai.prompts.store') }}`;
            const method = id ? 'PUT' : 'POST';
            const r = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                showPromptError((data && (data.message || (data.errors && JSON.stringify(data.errors)))) || 'Ошибка сохранения');
                return;
            }
            showPromptSuccess('Сохранено');
            await reloadPrompts();
            promptIdEl.value = data.prompt.id;
            btnActivatePrompt.disabled = false;
        } catch (err) {
            showPromptError('Ошибка сети');
        }
    });

    btnActivatePrompt && btnActivatePrompt.addEventListener('click', async () => {
        const id = promptIdEl.value;
        if (!id) return;
        try {
            const url = `{{ route('admin.ai.prompts.activate', ['prompt' => '__ID__']) }}`.replace('__ID__', id);
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                showPromptError('Не удалось активировать');
                return;
            }
            showPromptSuccess('Активировано');
            await reloadPrompts();
        } catch (e) {
            showPromptError('Ошибка сети');
        }
    });

    async function reloadPrompts() {
        const r = await fetch(`{{ route('admin.ai.prompts.index') }}`, {
            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        });
        const data = await r.json();
        const list = document.getElementById('prompt-list');
        if (!list) return;
        list.innerHTML = '';
        (data.prompts || []).forEach((p) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center prompt-item';
            btn.dataset.promptId = p.id;
            btn.dataset.promptTitle = p.title || '';
            btn.dataset.promptSystem = p.system_prompt || '';
            btn.innerHTML = `
                <span class="text-truncate" style="max-width: 280px;">
                    ${escapeHtml(p.title || ('Prompt #' + p.id))}
                    ${p.is_active ? '<span class="badge bg-success ms-2">Активный</span>' : ''}
                </span>
                <span class="text-muted small">#${p.id}</span>
            `;
            list.appendChild(btn);
        });
        if ((data.prompts || []).length === 0) {
            const d = document.createElement('div');
            d.className = 'p-3 text-muted';
            d.textContent = 'Промптов ещё нет.';
            list.appendChild(d);
        }
    }

    // ----- Chat -----
    const convList = document.getElementById('conv-list');
    const btnNewConv = document.getElementById('btn-new-conv');
    const chatTitle = document.getElementById('chat-title');
    const chatConvId = document.getElementById('chat-conv-id');
    const chatMessages = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');
    const chatError = document.getElementById('chat-error');
    const meetingApplyBar = document.getElementById('meeting-apply-bar');
    const btnMeetingApply = document.getElementById('btn-meeting-apply');
    const meetingAtEl = document.getElementById('meeting-at');
    const btnNewMeeting = document.getElementById('btn-new-meeting');

    const ctxQ = document.getElementById('ctx-q');
    const ctxClient = document.getElementById('ctx-client');
    const ctxProject = document.getElementById('ctx-project');
    const ctxTask = document.getElementById('ctx-task');

    let currentConversationId = null;

    function updateMeetingBar(conv) {
        if (!meetingApplyBar || !btnMeetingApply) return;
        if (conv && conv.kind === 'meeting' && !conv.meeting_finalized_at) {
            meetingApplyBar.classList.remove('d-none');
            btnMeetingApply.disabled = false;
        } else {
            meetingApplyBar.classList.add('d-none');
            btnMeetingApply.disabled = true;
        }
    }

    async function refreshConversationMeta() {
        if (!currentConversationId) {
            updateMeetingBar(null);
            return;
        }
        try {
            const url = `{{ route('admin.ai.conversations.show', ['conversation' => '__ID__']) }}`.replace('__ID__', currentConversationId);
            const r = await fetch(url, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
            const data = await r.json();
            if (r.ok && data.conversation) {
                updateMeetingBar(data.conversation);
            }
        } catch (e) { /* ignore */ }
    }

    function setChatError(msg) {
        chatError.textContent = msg || 'Ошибка';
        chatError.classList.remove('d-none');
    }
    function clearChatError() {
        chatError.classList.add('d-none');
    }

    function escapeHtml(s) {
        return (s || '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    function renderMessage(role, content) {
        const wrap = document.createElement('div');
        wrap.className = 'mb-3';
        const badge = role === 'assistant'
            ? '<span class="badge bg-primary">ИИ</span>'
            : (role === 'user' ? '<span class="badge bg-secondary">Вы</span>' : '<span class="badge bg-light text-dark">System</span>');
        wrap.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-1">${badge}<span class="text-muted small">${role}</span></div>
            <div class="border rounded p-2 bg-light" style="white-space: pre-wrap;">${escapeHtml(content || '')}</div>
        `;
        return wrap;
    }

    async function loadConversation(convId) {
        currentConversationId = convId;
        chatSend.disabled = !currentConversationId;
        clearChatError();
        chatMessages.innerHTML = '<div class="text-muted">Загрузка...</div>';
        try {
            const url = `{{ route('admin.ai.conversations.show', ['conversation' => '__ID__']) }}`.replace('__ID__', convId);
            const r = await fetch(url, { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
            const data = await r.json();
            if (!r.ok) {
                setChatError('Не удалось загрузить диалог');
                return;
            }
            chatTitle.textContent = data.conversation.title || ('Диалог #' + data.conversation.id);
            chatConvId.textContent = '#' + data.conversation.id;
            chatMessages.innerHTML = '';
            (data.messages || []).forEach((m) => {
                if (m.role === 'system') return; // keep UI clean
                chatMessages.appendChild(renderMessage(m.role, m.content));
            });
            if ((data.messages || []).filter(m => m.role !== 'system').length === 0) {
                chatMessages.innerHTML = '<div class="text-muted">Сообщений пока нет.</div>';
            }
            chatMessages.scrollTop = chatMessages.scrollHeight;
            updateMeetingBar(data.conversation);
        } catch (e) {
            setChatError('Ошибка сети');
        }
    }

    if (convList) {
        convList.addEventListener('click', (e) => {
            const btn = e.target.closest('.conv-item');
            if (!btn) return;
            loadConversation(btn.dataset.convId);
        });
    }

    btnNewConv && btnNewConv.addEventListener('click', async () => {
        clearChatError();
        try {
            const r = await fetch(`{{ route('admin.ai.conversations.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    title: null,
                    kind: 'general',
                    client_id: ctxClient.value || null,
                    project_id: ctxProject.value || null,
                    task_id: ctxTask.value || null,
                }),
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                setChatError('Не удалось создать диалог');
                return;
            }
            await reloadConversations();
            loadConversation(data.conversation.id);
        } catch (e) {
            setChatError('Ошибка сети');
        }
    });

    btnNewMeeting && btnNewMeeting.addEventListener('click', async () => {
        clearChatError();
        try {
            const payload = {
                kind: 'meeting',
                client_id: ctxClient.value || null,
                project_id: ctxProject.value || null,
                task_id: ctxTask.value || null,
            };
            if (meetingAtEl && meetingAtEl.value) {
                payload.meeting_at = meetingAtEl.value;
            }
            const r = await fetch(`{{ route('admin.ai.conversations.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                setChatError('Не удалось создать совещание');
                return;
            }
            await reloadConversations();
            loadConversation(data.conversation.id);
        } catch (e) {
            setChatError('Ошибка сети');
        }
    });

    btnMeetingApply && btnMeetingApply.addEventListener('click', async () => {
        if (!currentConversationId) return;
        clearChatError();
        btnMeetingApply.disabled = true;
        try {
            const url = `{{ route('admin.ai.conversations.apply-meeting-tasks', ['conversation' => '__ID__']) }}`.replace('__ID__', currentConversationId);
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                setChatError(data.message || 'Не удалось создать задачи');
                btnMeetingApply.disabled = false;
                return;
            }
            if (data.assistant_message) {
                chatMessages.appendChild(renderMessage('assistant', data.assistant_message.content || ''));
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            chatTitle.textContent = data.conversation && data.conversation.title ? data.conversation.title : chatTitle.textContent;
            updateMeetingBar(data.conversation);
            await reloadConversations();
        } catch (e) {
            setChatError('Ошибка сети');
            btnMeetingApply.disabled = false;
        }
    });

    chatForm && chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearChatError();
        const text = (chatInput.value || '').trim();
        if (!currentConversationId || !text) return;
        chatInput.value = '';
        chatMessages.appendChild(renderMessage('user', text));
        chatMessages.scrollTop = chatMessages.scrollHeight;

        const typing = document.createElement('div');
        typing.className = 'text-muted small';
        typing.textContent = 'ИИ печатает...';
        chatMessages.appendChild(typing);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        try {
            const url = `{{ route('admin.ai.messages.store', ['conversation' => '__ID__']) }}`.replace('__ID__', currentConversationId);
            const r = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    content: text,
                    client_id: ctxClient.value || null,
                    project_id: ctxProject.value || null,
                    task_id: ctxTask.value || null,
                }),
            });
            const data = await r.json();
            typing.remove();
            if (!r.ok || !data.ok) {
                setChatError('Не удалось получить ответ');
                return;
            }
            chatMessages.appendChild(renderMessage('assistant', data.assistant_message.content || ''));
            chatMessages.scrollTop = chatMessages.scrollHeight;
            await reloadConversations();
            await refreshConversationMeta();
        } catch (e) {
            typing.remove();
            setChatError('Ошибка сети');
        }
    });

    async function reloadConversations() {
        const r = await fetch(`{{ route('admin.ai.conversations.index') }}`, {
            headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
        });
        const data = await r.json();
        const list = document.getElementById('conv-list');
        if (!list) return;
        list.innerHTML = '';
        (data.conversations || []).forEach((c) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action conv-item';
            btn.dataset.convId = c.id;
            btn.dataset.convKind = c.kind || 'general';
            btn.dataset.convTitle = c.title || ('Диалог #' + c.id);
            const badge = (c.kind === 'meeting') ? '<span class="badge bg-info ms-1">совещ.</span>' : '';
            btn.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-truncate">${escapeHtml(btn.dataset.convTitle)}</span>
                    <span class="text-muted small text-nowrap ms-1">#${c.id} ${badge}</span>
                </div>
            `;
            list.appendChild(btn);
        });
        if ((data.conversations || []).length === 0) {
            const d = document.createElement('div');
            d.className = 'p-3 text-muted';
            d.textContent = 'Диалогов ещё нет.';
            list.appendChild(d);
        }
    }

    // ----- Context search -----
    function fillSelect(select, items, labelFn) {
        select.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '— не выбран —';
        select.appendChild(opt0);
        (items || []).forEach((item) => {
            const o = document.createElement('option');
            o.value = item.id;
            o.textContent = labelFn(item);
            select.appendChild(o);
        });
    }

    async function reloadContext() {
        const q = (ctxQ && ctxQ.value) ? ctxQ.value.trim() : '';
        const url = new URL(`{{ route('admin.ai.context') }}`, window.location.origin);
        if (q) url.searchParams.set('q', q);
        url.searchParams.set('limit', '20');

        const r = await fetch(url.toString(), { headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'} });
        const data = await r.json();
        fillSelect(ctxClient, data.clients, (c) => `${c.first_name} ${c.last_name}`.trim());
        fillSelect(ctxProject, data.projects, (p) => p.name);
        fillSelect(ctxTask, data.tasks, (t) => `#${t.id} ${t.title}` );
    }

    function debounce(fn, ms) {
        let t = null;
        return function () {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, arguments), ms);
        };
    }

    if (ctxQ) {
        ctxQ.addEventListener('input', debounce(reloadContext, 300));
    }

    // initialize context selects
    if (ctxClient && ctxProject && ctxTask) {
        reloadContext();
    }

    // ----- События компании -----
    const companyEventForm = document.getElementById('company-event-form');
    const companyEventId = document.getElementById('company-event-id');
    const companyEventDesc = document.getElementById('company-event-description');
    const companyEventList = document.getElementById('company-event-list');
    const btnCompanyEventNew = document.getElementById('btn-company-event-new');
    const companyEventError = document.getElementById('company-event-error');
    const companyEventSuccess = document.getElementById('company-event-success');

    function showCompanyEventErr(msg) {
        if (!companyEventError) return;
        companyEventError.textContent = msg || 'Ошибка';
        companyEventError.classList.remove('d-none');
        if (companyEventSuccess) companyEventSuccess.classList.add('d-none');
    }
    function showCompanyEventOk(msg) {
        if (!companyEventSuccess) return;
        companyEventSuccess.textContent = msg || 'Готово';
        companyEventSuccess.classList.remove('d-none');
        companyEventError && companyEventError.classList.add('d-none');
        setTimeout(() => companyEventSuccess.classList.add('d-none'), 2000);
    }
    function clearCompanyEventForm() {
        if (companyEventId) companyEventId.value = '';
        if (companyEventDesc) companyEventDesc.value = '';
    }

    function truncateEvText(s, n) {
        s = s || '';
        if (s.length <= n) return s;
        return s.slice(0, n - 1) + '…';
    }

    function formatEventDate(ev) {
        if (!ev.created_at) return '';
        const t = String(ev.created_at);
        return t.replace('T', ' ').slice(0, 16);
    }

    function buildCompanyEventRow(ev) {
        const div = document.createElement('div');
        div.className = 'list-group-item company-event-item py-3';
        div.dataset.eventId = String(ev.id);
        const dt = formatEventDate(ev);
        div.innerHTML =
            '<div class="d-flex justify-content-between gap-2 flex-wrap">' +
            '<span class="text-muted small">#' + ev.id + ' · ' + escapeHtml(dt) + '</span>' +
            '<div class="btn-group btn-group-sm">' +
            '<button type="button" class="btn btn-outline-primary btn-company-event-edit">Редактировать</button>' +
            '<button type="button" class="btn btn-outline-danger btn-company-event-delete">Удалить</button>' +
            '</div></div>' +
            '<div class="mt-2 text-break" style="white-space: pre-wrap;">' +
            escapeHtml(truncateEvText(ev.description, 400)) + '</div>';
        return div;
    }

    function bindCompanyEventItem(div) {
        div.querySelector('.btn-company-event-edit')?.addEventListener('click', async () => {
            const id = div.dataset.eventId;
            try {
                const url = `{{ route('admin.ai.company-events.show', ['companyEvent' => '__ID__']) }}`.replace('__ID__', id);
                const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await r.json();
                if (!r.ok || !data.event) {
                    showCompanyEventErr('Не удалось загрузить событие');
                    return;
                }
                if (companyEventId) companyEventId.value = String(data.event.id);
                if (companyEventDesc) {
                    companyEventDesc.value = data.event.description || '';
                    companyEventDesc.focus();
                }
            } catch (e) {
                showCompanyEventErr('Ошибка сети');
            }
        });
        div.querySelector('.btn-company-event-delete')?.addEventListener('click', async () => {
            if (!confirm('Удалить это событие из журнала?')) return;
            const id = div.dataset.eventId;
            try {
                const url = `{{ route('admin.ai.company-events.destroy', ['companyEvent' => '__ID__']) }}`.replace('__ID__', id);
                const r = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await r.json();
                if (!r.ok || !data.ok) {
                    showCompanyEventErr('Не удалось удалить');
                    return;
                }
                div.remove();
                showCompanyEventOk('Удалено');
                if (companyEventId && String(companyEventId.value) === String(id)) clearCompanyEventForm();
                if (companyEventList && !companyEventList.querySelector('.company-event-item')) {
                    companyEventList.innerHTML = '<div class="p-3 text-muted" id="company-event-empty">Событий пока нет.</div>';
                }
            } catch (e) {
                showCompanyEventErr('Ошибка сети');
            }
        });
    }

    document.querySelectorAll('#company-event-list .company-event-item').forEach(bindCompanyEventItem);

    btnCompanyEventNew && btnCompanyEventNew.addEventListener('click', () => {
        clearCompanyEventForm();
        if (companyEventError) companyEventError.classList.add('d-none');
    });

    companyEventForm && companyEventForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (companyEventError) companyEventError.classList.add('d-none');
        const desc = (companyEventDesc && companyEventDesc.value) ? companyEventDesc.value.trim() : '';
        if (!desc) return;
        const id = companyEventId && companyEventId.value;
        try {
            let url = `{{ route('admin.ai.company-events.store') }}`;
            let method = 'POST';
            if (id) {
                url = `{{ route('admin.ai.company-events.update', ['companyEvent' => '__ID__']) }}`.replace('__ID__', id);
                method = 'PUT';
            }
            const r = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ description: desc }),
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                showCompanyEventErr((data && data.message) || 'Не сохранено');
                return;
            }
            showCompanyEventOk(id ? 'Обновлено' : 'Добавлено');
            if (data.event && companyEventList) {
                const empty = companyEventList.querySelector('#company-event-empty');
                if (empty) empty.remove();
                if (!id) {
                    const row = buildCompanyEventRow(data.event);
                    companyEventList.insertBefore(row, companyEventList.firstChild);
                    bindCompanyEventItem(row);
                } else {
                    const old = companyEventList.querySelector('[data-event-id="' + id + '"]');
                    if (old) {
                        const row = buildCompanyEventRow(data.event);
                        old.replaceWith(row);
                        bindCompanyEventItem(row);
                    }
                }
            }
            clearCompanyEventForm();
        } catch (err) {
            showCompanyEventErr('Ошибка сети');
        }
    });

    // ----- Telegram group messages (duplicate of group chat) -----
    const tgChat = document.getElementById('tg-chat');
    const tgMetaHint = document.getElementById('tg-meta-hint');
    const btnTgRefresh = document.getElementById('btn-tg-refresh');
    const tabTelegram = document.getElementById('tab-telegram');
    const tgChatSelect = document.getElementById('tg-chat-select');

    async function loadTelegramMessages() {
        if (!tgChat) return;
        tgChat.innerHTML = '<div class="text-muted small">Загрузка…</div>';
        try {
            const url = new URL(`{{ route('admin.ai.telegram-messages') }}`, window.location.origin);
            url.searchParams.set('limit', '400');
            if (tgChatSelect && tgChatSelect.value) {
                url.searchParams.set('chat_id', tgChatSelect.value);
            }
            const r = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await r.json();
            if (!r.ok || !data.ok) {
                tgChat.innerHTML = '<div class="text-danger">Не удалось загрузить сообщения.</div>';
                return;
            }
            if (tgMetaHint) {
                if (!data.chat_id && data.hint) {
                    tgMetaHint.textContent = data.hint;
                } else {
                    let meta = 'Chat ID: <code>' + escapeHtml(String(data.chat_id || '')) + '</code> · в базе: ' + (data.total_in_db ?? 0)
                        + ' · ответов бота в БД: ' + (data.outgoing_in_db ?? 0)
                        + ' · показано: ' + (data.loaded ?? 0);
                    if (data.hint) {
                        meta += '<br><span class="text-warning">' + escapeHtml(data.hint) + '</span>';
                    }
                    tgMetaHint.innerHTML = meta;
                }
            }
            tgChat.innerHTML = '';
            const list = data.messages || [];
            if (list.length === 0) {
                tgChat.innerHTML = '<div class="text-muted">Сообщений пока нет. Убедитесь, что webhook включён и бот видит сообщения в группе (privacy off).</div>';
                return;
            }
            list.forEach((m) => {
                const row = document.createElement('div');
                row.className = 'mb-3 pb-2 border-bottom';
                const body = m.text
                    ? '<div class="text-break">' + escapeHtml(m.text) + '</div>'
                    : '<div class="text-muted"><em>(нет текста: стикер, медиа и т.п.)</em></div>';
                row.innerHTML =
                    '<div class="small text-secondary mb-1">' + escapeHtml(m.at || '') + ' · ' + escapeHtml(m.author || '') + '</div>' + body;
                tgChat.appendChild(row);
            });
            tgChat.scrollTop = tgChat.scrollHeight;
        } catch (e) {
            tgChat.innerHTML = '<div class="text-danger">Ошибка сети</div>';
        }
    }

    if (btnTgRefresh) {
        btnTgRefresh.addEventListener('click', () => loadTelegramMessages());
    }
    if (tgChatSelect) {
        tgChatSelect.addEventListener('change', () => loadTelegramMessages());
    }
    if (tabTelegram) {
        tabTelegram.addEventListener('shown.bs.tab', () => loadTelegramMessages());
    }
})();
</script>
@endpush
@endsection

