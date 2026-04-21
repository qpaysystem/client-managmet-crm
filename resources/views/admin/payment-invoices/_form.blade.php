@php
    /** @var \App\Models\PaymentInvoice|null $invoice */
@endphp

<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label">Статья расходов *</label>
        <input type="text" name="expense_article" class="form-control @error('expense_article') is-invalid @enderror"
               value="{{ old('expense_article', $invoice?->expense_article) }}" required>
        @error('expense_article')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Сумма платежа *</label>
        <input type="number" name="amount" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
               value="{{ old('amount', $invoice?->amount) }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

    <div class="col-md-4">
        <label class="form-label">Приоритет оплаты *</label>
        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
            @foreach(\App\Models\PaymentInvoice::priorityLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $invoice?->priority ?? \App\Models\PaymentInvoice::PRIORITY_PLANNED) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

