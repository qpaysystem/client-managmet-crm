@extends('cabinet.layout')
@section('title', 'Транзакции')
@section('content')
<h1 class="h4 mb-4">История операций</h1>

<form method="get" action="{{ route('cabinet.transactions') }}" class="row g-2 mb-4">
    <div class="col-auto">
        <label class="form-label visually-hidden">Клиент</label>
        <select name="client_id" class="form-select form-select-sm">
            <option value="">Все</option>
            @foreach($clientsForFilter ?? [$client] as $c)
                <option value="{{ $c->id }}" @selected(request('client_id') == (string)$c->id)>{{ $c->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Тип операции</label>
        <select name="operation_type" class="form-select form-select-sm">
            <option value="">Все типы</option>
            @foreach(\App\Models\BalanceTransaction::operationTypeLabels() as $value => $label)
                <option value="{{ $value }}" @selected(request('operation_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Пополнение / списание</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">Все</option>
            <option value="deposit" @selected(request('type') === 'deposit')>Пополнение</option>
            <option value="withdraw" @selected(request('type') === 'withdraw')>Списание</option>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">Дата с</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="С">
    </div>
    <div class="col-auto">
        <label class="form-label visually-hidden">По</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="По">
    </div>
    <div class="col-auto d-flex align-items-end gap-1">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Показать</button>
        <a href="{{ route('cabinet.transactions') }}" class="btn btn-outline-secondary btn-sm">Сбросить</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Дата</th>
                        <th>Клиент</th>
                        <th>Тип</th>
                        <th>Залог / Проект</th>
                        <th>Сумма</th>
                        <th>Баланс после</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td>{{ $t->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $t->client?->full_name ?? $client->full_name }}</td>
                        <td>{{ $t->operation_type_label }}</td>
                        <td>@if($t->operation_type === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE){{ $t->project?->name }}@if($t->projectExpenseItem) — {{ $t->projectExpenseItem->name }}@endif @else{{ $t->product?->name ?? '—' }}@endif</td>
                        <td class="{{ $t->type === 'deposit' ? 'text-success' : 'text-danger' }}">
                            {{ $t->type === 'deposit' ? '+' : '−' }}{{ number_format($t->amount, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}
                        </td>
                        <td>{{ number_format($t->balance_after, 2) }}</td>
                        <td class="small text-muted">{{ Str::limit($t->comment, 40) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">Нет операций</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@if($transactions->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $transactions->links() }}
    </div>
@endif
@endsection
