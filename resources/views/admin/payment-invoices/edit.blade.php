@extends('layouts.admin')
@section('title', 'Изменить счёт')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0">Изменить счёт</h1>
    <a href="{{ route('admin.payment-invoices.index') }}" class="btn btn-outline-secondary">Назад</a>
</div>

<form method="post" action="{{ route('admin.payment-invoices.update', $invoice) }}">
    @csrf
    @method('PUT')
    @include('admin.payment-invoices._form', ['invoice' => $invoice, 'users' => $users, 'projects' => $projects, 'expenseItems' => $expenseItems])
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="{{ route('admin.payment-invoices.index') }}" class="btn btn-secondary">Отмена</a>
    </div>
</form>
@endsection

