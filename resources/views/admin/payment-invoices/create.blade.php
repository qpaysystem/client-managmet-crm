@extends('layouts.admin')
@section('title', 'Новый счёт')
@section('content')
<h1 class="h4 mb-4">Новый счёт на оплату</h1>

<form method="post" action="{{ route('admin.payment-invoices.store') }}">
    @csrf
    @include('admin.payment-invoices._form', ['invoice' => null, 'users' => $users])
    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Создать</button>
        <a href="{{ route('admin.payment-invoices.index') }}" class="btn btn-secondary">Отмена</a>
    </div>
</form>
@endsection

