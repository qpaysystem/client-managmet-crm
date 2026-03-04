@extends('layouts.admin')
@section('title', 'Создать транзакцию')
@section('content')
<h1 class="h4 mb-4">Создать транзакцию</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="post" action="{{ route('admin.transactions.store') }}" id="transaction-form" class="card mb-4">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Дата транзакции <span class="text-danger">*</span></label>
                <input type="datetime-local" name="transaction_date" id="transaction_date" class="form-control @error('transaction_date') is-invalid @enderror" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
                @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Клиент <span class="text-danger">*</span></label>
                <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                    <option value="">— Выберите клиента —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->full_name }}</option>
                    @endforeach
                </select>
                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Тип операции <span class="text-danger">*</span></label>
                <select name="operation_type" id="operation_type" class="form-select @error('operation_type') is-invalid @enderror" required>
                    @foreach(\App\Models\BalanceTransaction::operationTypeLabels() as $value => $label)
                        <option value="{{ $value }}" @selected(old('operation_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('operation_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-3" id="project_wrap" style="display: {{ old('operation_type') === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE ? 'block' : 'none' }};">
                <label class="form-label">Проект</label>
                <select name="project_id" id="project_id" class="form-select">
                    <option value="">— Выберите проект —</option>
                    @foreach($projects ?? [] as $proj)
                        <option value="{{ $proj->id }}" @selected(old('project_id') == $proj->id)>{{ $proj->name }}</option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-3" id="expense_item_wrap" style="display: {{ old('operation_type') === \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE ? 'block' : 'none' }};">
                <label class="form-label">Статья расхода</label>
                <select name="project_expense_item_id" id="project_expense_item_id" class="form-select">
                    <option value="">— Сначала выберите проект —</option>
                </select>
                @error('project_expense_item_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-2" id="loan_days_wrap" style="display: {{ old('operation_type', \App\Models\BalanceTransaction::OPERATION_LOAN) === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'block' : 'none' }};">
                <label class="form-label">Дней займа</label>
                <input type="number" name="loan_days" id="loan_days" class="form-control @error('loan_days') is-invalid @enderror" value="{{ old('loan_days') }}" min="1" max="3650" placeholder="дней">
                @error('loan_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 col-lg-3" id="product_pledge_wrap" style="display: {{ old('operation_type', \App\Models\BalanceTransaction::OPERATION_LOAN) === \App\Models\BalanceTransaction::OPERATION_LOAN ? 'block' : 'none' }};">
                <label class="form-label">Залог (товар)</label>
                <select name="product_id" id="product_id" class="form-select">
                    <option value="">— не выбран</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id') == $prod->id)>{{ $prod->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="form-label">Сумма <span class="text-danger">*</span></label>
                <input type="number" name="amount" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" placeholder="0.00" value="{{ old('amount') }}" required>
                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Комментарий</label>
                <input type="text" name="comment" class="form-control" placeholder="Комментарий" value="{{ old('comment') }}">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Создать транзакцию</button>
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">Отмена</a>
        </div>
    </div>
</form>

<script type="application/json" id="projects-expense-items-data">@json($projects ? $projects->mapWithKeys(function ($p) { return [$p->id => $p->expenseItems->map(function ($e) { return ['id' => $e->id, 'name' => $e->name]; })->values()->all()]; })->all() : [])</script>
<script>
(function() {
    var op = document.getElementById('operation_type');
    var wrap = document.getElementById('loan_days_wrap');
    var wrapPledge = document.getElementById('product_pledge_wrap');
    var projectWrap = document.getElementById('project_wrap');
    var expenseItemWrap = document.getElementById('expense_item_wrap');
    var projectSelect = document.getElementById('project_id');
    var expenseItemSelect = document.getElementById('project_expense_item_id');
    var inputDays = document.getElementById('loan_days');
    var projectsDataEl = document.getElementById('projects-expense-items-data');
    var projectsData = projectsDataEl ? JSON.parse(projectsDataEl.textContent || '{}') : {};
    var oldExpenseItemId = {{ json_encode(old('project_expense_item_id')) }};

    function filterExpenseItems() {
        if (!expenseItemSelect) return;
        var projectId = projectSelect && projectSelect.value;
        var saved = oldExpenseItemId || (expenseItemSelect && expenseItemSelect.value);
        expenseItemSelect.innerHTML = '<option value="">— Выберите статью —</option>';
        if (projectId && projectsData[projectId]) {
            projectsData[projectId].forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                if (String(item.id) === String(saved)) opt.selected = true;
                expenseItemSelect.appendChild(opt);
            });
        }
        oldExpenseItemId = null;
    }

    function toggle() {
        var isLoan = op && op.value === '{{ \App\Models\BalanceTransaction::OPERATION_LOAN }}';
        var isProjectExpense = op && op.value === '{{ \App\Models\BalanceTransaction::OPERATION_PROJECT_EXPENSE }}';
        if (wrap) wrap.style.display = isLoan ? 'block' : 'none';
        if (wrapPledge) wrapPledge.style.display = isLoan ? 'block' : 'none';
        if (projectWrap) projectWrap.style.display = isProjectExpense ? 'block' : 'none';
        if (expenseItemWrap) expenseItemWrap.style.display = isProjectExpense ? 'block' : 'none';
        if (projectSelect) projectSelect.required = isProjectExpense;
        if (expenseItemSelect) expenseItemSelect.required = isProjectExpense;
        if (inputDays) inputDays.required = isLoan;
        if (isProjectExpense) filterExpenseItems();
    }

    if (projectSelect) projectSelect.addEventListener('change', filterExpenseItems);
    if (op) op.addEventListener('change', toggle);
    toggle();
    if (projectSelect && projectSelect.value) filterExpenseItems();
})();
</script>
@endsection
