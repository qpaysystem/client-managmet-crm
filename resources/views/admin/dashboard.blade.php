@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<h1 class="h4 mb-4">Dashboard</h1>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Всего клиентов</h6>
                <h3 class="mb-0">{{ $stats['clients_total'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Активных</h6>
                <h3 class="mb-0">{{ $stats['clients_active'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Общая сумма займов (остаток)</h6>
                <h3 class="mb-0">{{ number_format($stats['balance_total'], 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</h3>
                <small class="text-muted">Выданные займы минус возвраты</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted">Баланс товаров (ТМЦ)</h6>
                <h3 class="mb-0">{{ number_format($stats['products_balance'], 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</h3>
                <small class="text-muted">{{ $stats['products_count'] }} позиций в ТМЦ</small>
            </div>
        </div>
    </div>
</div>
<h5 class="mb-3">Займы клиентов</h5>
<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 2rem;"></th>
                    <th>Клиент</th>
                    <th class="text-end">Остаток займа</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($loansByClientList ?? [] as $row)
                @php
                    $client = $row['client'];
                    $transactions = $row['transactions'] ?? collect();
                @endphp
                <tr class="align-middle">
                    <td class="pe-0">
                        @if($transactions->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-link text-secondary p-0 border-0" style="min-width: 1.5rem;" data-bs-toggle="collapse" data-bs-target="#loan-docs-{{ $client->id }}" aria-expanded="false" title="Показать документы">
                                <i class="bi bi-chevron-down loan-collapse-icon"></i>
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($transactions->isNotEmpty())
                            <span class="text-primary" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#loan-docs-{{ $client->id }}" aria-expanded="false">{{ $client->full_name }}</span>
                        @else
                            {{ $client->full_name }}
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($row['amount'], 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                    <td><a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-primary">Карточка</a></td>
                </tr>
                @if($transactions->isNotEmpty())
                <tr class="table-light">
                    <td colspan="4" class="p-0 border-0">
                        <div class="collapse" id="loan-docs-{{ $client->id }}">
                            <div class="px-3 pb-3 pt-0">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Дата</th>
                                            <th>Операция</th>
                                            <th class="text-end">Сумма</th>
                                            <th>Залог / проект</th>
                                            <th>Комментарий</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $t)
                                        <tr>
                                            <td>{{ $t->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                            <td>{{ $t->operation_type_label ?? ($t->operation_type === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'Займ' : 'Возврат займа') }}</td>
                                            <td class="text-end {{ $t->type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                                {{ $t->type === 'deposit' ? '+' : '−' }}{{ number_format($t->amount, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}
                                            </td>
                                            <td>
                                                @if($t->operation_type === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE)
                                                    {{ $t->project?->name }}@if($t->projectExpenseItem) — {{ $t->projectExpenseItem->name }}@endif
                                                @else
                                                    {{ $t->product?->name ?? '—' }}
                                                @endif
                                            </td>
                                            <td class="small text-muted">{{ Str::limit($t->comment ?? '', 50) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="4" class="text-muted">Нет данных о займах</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
(function() {
    document.querySelectorAll('.collapse[id^="loan-docs-"]').forEach(function(collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.loan-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-down'); icon.classList.add('bi-chevron-up'); }
            });
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.loan-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-up'); icon.classList.add('bi-chevron-down'); }
            });
        });
    });
})();
</script>

<h5 class="mb-3">Вложения в проект</h5>
<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 2rem;"></th>
                    <th>Клиент</th>
                    <th class="text-end">Сумма вложений</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($investmentsByClientList ?? [] as $row)
                @php
                    $client = $row['client'];
                    $records = $row['records'] ?? collect();
                @endphp
                <tr class="align-middle">
                    <td class="pe-0">
                        @if($records->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-link text-secondary p-0 border-0" style="min-width: 1.5rem;" data-bs-toggle="collapse" data-bs-target="#invest-docs-{{ $client->id }}" aria-expanded="false" title="Показать документы">
                                <i class="bi bi-chevron-down invest-collapse-icon"></i>
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($records->isNotEmpty())
                            <span class="text-primary" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#invest-docs-{{ $client->id }}" aria-expanded="false">{{ $client->full_name }}</span>
                        @else
                            {{ $client->full_name }}
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($row['amount'], 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                    <td><a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-primary">Карточка</a></td>
                </tr>
                @if($records->isNotEmpty())
                <tr class="table-light">
                    <td colspan="4" class="p-0 border-0">
                        <div class="collapse" id="invest-docs-{{ $client->id }}">
                            <div class="px-3 pb-3 pt-0">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Дата</th>
                                            <th>Проект</th>
                                            <th>Статья расхода</th>
                                            <th class="text-end">Сумма</th>
                                            <th>Комментарий</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($records as $inv)
                                        <tr>
                                            <td>{{ $inv->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                            <td>@if($inv->project)<a href="{{ route('admin.projects.show', $inv->project) }}">{{ $inv->project->name }}</a>@else — @endif</td>
                                            <td>{{ $inv->expense_item_name ?? '—' }}</td>
                                            <td class="text-end">{{ number_format($inv->amount, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                                            <td class="small text-muted">{{ Str::limit($inv->comment ?? '', 50) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="4" class="text-muted">Нет вложений в проекты</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
(function() {
    document.querySelectorAll('.collapse[id^="invest-docs-"]').forEach(function(collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.invest-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-down'); icon.classList.add('bi-chevron-up'); }
            });
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.invest-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-up'); icon.classList.add('bi-chevron-down'); }
            });
        });
    });
})();
</script>

<h5 class="mb-3">Расходы на проект</h5>
<div class="card mb-4">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 2rem;"></th>
                    <th>Клиент</th>
                    <th class="text-end">Сумма расходов</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($expensesByClientList ?? [] as $row)
                @php
                    $client = $row['client'];
                    $transactions = $row['transactions'] ?? collect();
                @endphp
                <tr class="align-middle">
                    <td class="pe-0">
                        @if($transactions->isNotEmpty())
                            <button type="button" class="btn btn-sm btn-link text-secondary p-0 border-0" style="min-width: 1.5rem;" data-bs-toggle="collapse" data-bs-target="#expense-docs-{{ $client->id }}" aria-expanded="false" title="Показать документы">
                                <i class="bi bi-chevron-down expense-collapse-icon"></i>
                            </button>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($transactions->isNotEmpty())
                            <span class="text-primary" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#expense-docs-{{ $client->id }}" aria-expanded="false">{{ $client->full_name }}</span>
                        @else
                            {{ $client->full_name }}
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($row['amount'], 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                    <td><a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-primary">Карточка</a></td>
                </tr>
                @if($transactions->isNotEmpty())
                <tr class="table-light">
                    <td colspan="4" class="p-0 border-0">
                        <div class="collapse" id="expense-docs-{{ $client->id }}">
                            <div class="px-3 pb-3 pt-0">
                                <table class="table table-sm table-bordered mb-0 bg-white">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Дата</th>
                                            <th>Проект</th>
                                            <th>Статья расхода</th>
                                            <th class="text-end">Сумма</th>
                                            <th>Комментарий</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $t)
                                        <tr>
                                            <td>{{ $t->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                            <td>@if($t->project)<a href="{{ route('admin.projects.show', $t->project) }}">{{ $t->project->name }}</a>@else — @endif</td>
                                            <td>{{ $t->projectExpenseItem?->name ?? '—' }}</td>
                                            <td class="text-end">{{ number_format($t->amount, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                                            <td class="small text-muted">{{ Str::limit($t->comment ?? '', 50) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="4" class="text-muted">Нет расходов на проект</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
(function() {
    document.querySelectorAll('.collapse[id^="expense-docs-"]').forEach(function(collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.expense-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-down'); icon.classList.add('bi-chevron-up'); }
            });
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            var id = this.id;
            document.querySelectorAll('[data-bs-target="#' + id + '"]').forEach(function(btn) {
                var icon = btn.querySelector('.expense-collapse-icon');
                if (icon) { icon.classList.remove('bi-chevron-up'); icon.classList.add('bi-chevron-down'); }
            });
        });
    });
})();
</script>

<h5 class="mb-3">Последние клиенты</h5>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Имя</th><th>Email</th><th>Займ (остаток)</th><th></th></tr></thead>
            <tbody>
                @forelse($recentClients as $c)
                <tr>
                    <td>{{ $c->full_name }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ number_format($c->loan_balance ?? 0, 2) }} {{ \App\Models\Setting::get('currency', 'RUB') }}</td>
                    <td><a href="{{ route('admin.clients.show', $c) }}" class="btn btn-sm btn-outline-primary">Открыть</a></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted">Нет клиентов</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
