@extends('layouts.admin')
@section('title', 'Счета на оплату')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Счета на оплату</h1>
    <a href="{{ route('admin.payment-invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Добавить счёт</a>
</div>

<form method="get" action="{{ route('admin.payment-invoices.index') }}" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label form-label-sm">Поиск</label>
            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="статья или комментарии">
        </div>
        <div class="col-md-2">
            <label class="form-label form-label-sm">Статус</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Все</option>
                @foreach(\App\Models\PaymentInvoice::statusLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label form-label-sm">Приоритет</label>
            <select name="priority" class="form-select form-select-sm">
                <option value="">Все</option>
                @foreach(\App\Models\PaymentInvoice::priorityLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label form-label-sm">Проект</label>
            <select name="project_id" class="form-select form-select-sm">
                <option value="">Все</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" @selected((string) request('project_id') === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label form-label-sm">Ответственный</label>
            <select name="responsible_user_id" class="form-select form-select-sm">
                <option value="">Все</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('responsible_user_id') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-secondary w-100">Показать</button>
            <a href="{{ route('admin.payment-invoices.index') }}" class="btn btn-sm btn-outline-secondary w-100">Сброс</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Проект</th>
                    <th>Статья</th>
                    <th class="text-end">Сумма</th>
                    <th>Получен</th>
                    <th>Крайний срок</th>
                    <th>Приоритет</th>
                    <th>Статус</th>
                    <th>Ответственный</th>
                    <th>Комментарий</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->project ? \Illuminate\Support\Str::limit($invoice->project->name, 30) : '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($invoice->projectExpenseItem?->name ?? $invoice->expense_article, 60) }}</td>
                        <td class="text-end">{{ number_format((float) $invoice->amount, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                        <td>{{ $invoice->received_date ? $invoice->received_date->format('d.m.Y') : '—' }}</td>
                        <td>
                            @php
                                $isOverdue = $invoice->due_date && $invoice->due_date->isPast();
                                $isSoon = !$isOverdue && $invoice->due_date && $invoice->due_date->diffInDays(now()) <= 3;
                            @endphp
                            @if($invoice->due_date)
                                <span class="{{ $isOverdue ? 'text-danger fw-semibold' : ($isSoon ? 'text-warning fw-semibold' : '') }}">
                                    {{ $invoice->due_date->format('d.m.Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @php
                                $badge = match($invoice->priority) {
                                    \App\Models\PaymentInvoice::PRIORITY_URGENT => 'danger',
                                    \App\Models\PaymentInvoice::PRIORITY_IMMEDIATE => 'warning',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ $invoice->priority_label }}</span>
                        </td>
                        <td>
                            @php
                                $statusBadge = $invoice->status === \App\Models\PaymentInvoice::STATUS_PAID ? 'success' : 'secondary';
                            @endphp
                            <span class="badge bg-{{ $statusBadge }}">{{ $invoice->status_label }}</span>
                        </td>
                        <td>{{ $invoice->responsibleUser ? $invoice->responsibleUser->name : '—' }}</td>
                        <td class="text-muted">{{ $invoice->comments ? \Illuminate\Support\Str::limit($invoice->comments, 60) : '—' }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.payment-invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Изменить</a>
                            <form method="post" action="{{ route('admin.payment-invoices.destroy', $invoice) }}" class="d-inline" onsubmit="return confirm('Удалить счёт?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-muted">Пока нет счетов</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection

