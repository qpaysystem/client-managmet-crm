@php
    /** @var \App\Models\PaymentInvoice|null $invoice */
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Сумма платежа *</label>
        <input type="number" name="amount" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $invoice?->amount) }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Проект *</label>
        <select name="project_id" id="pi_project_id" class="form-select @error('project_id') is-invalid @enderror" required>
            <option value="">—</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}" @selected((string) old('project_id', $invoice?->project_id) === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Статья расходов *</label>
        <select name="project_expense_item_id" id="pi_project_expense_item_id" class="form-select @error('project_expense_item_id') is-invalid @enderror" required>
            <option value="">—</option>
            @foreach($expenseItems as $it)
                <option value="{{ $it->id }}" @selected((string) old('project_expense_item_id', $invoice?->project_expense_item_id) === (string) $it->id)>{{ $it->name }}</option>
            @endforeach
        </select>
        @error('project_expense_item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Статус *</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach(\App\Models\PaymentInvoice::statusLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $invoice?->status ?? \App\Models\PaymentInvoice::STATUS_UNPAID) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Приоритет оплаты *</label>
        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
            @foreach(\App\Models\PaymentInvoice::priorityLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $invoice?->priority ?? \App\Models\PaymentInvoice::PRIORITY_PLANNED) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Ответственный</label>
        <select name="responsible_user_id" class="form-select @error('responsible_user_id') is-invalid @enderror">
            <option value="">—</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((string) old('responsible_user_id', $invoice?->responsible_user_id) === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('responsible_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Дата получения</label>
        <input type="date" name="received_date" class="form-control @error('received_date') is-invalid @enderror"
               value="{{ old('received_date', $invoice?->received_date?->format('Y-m-d')) }}">
        @error('received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Крайний срок оплаты</label>
        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
               value="{{ old('due_date', $invoice?->due_date?->format('Y-m-d')) }}">
        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Комментарии</label>
        <textarea name="comments" class="form-control @error('comments') is-invalid @enderror" rows="3">{{ old('comments', $invoice?->comments) }}</textarea>
        @error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
(() => {
    const projectSelect = document.getElementById('pi_project_id');
    const expenseSelect = document.getElementById('pi_project_expense_item_id');
    if (!projectSelect || !expenseSelect) return;

    const loadExpenseItems = async (projectId) => {
        expenseSelect.innerHTML = '<option value="">—</option>';
        if (!projectId) return;
        try {
            const resp = await fetch(`{{ route('admin.payment-invoices.expense-items') }}?project_id=${encodeURIComponent(projectId)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            const items = Array.isArray(data.items) ? data.items : [];
            for (const it of items) {
                const opt = document.createElement('option');
                opt.value = it.id;
                opt.textContent = it.name;
                expenseSelect.appendChild(opt);
            }
        } catch (e) {
            // ignore
        }
    };

    projectSelect.addEventListener('change', async () => {
        await loadExpenseItems(projectSelect.value);
    });
})();
</script>
@endpush

