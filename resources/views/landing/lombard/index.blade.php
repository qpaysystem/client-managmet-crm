@extends('landing.lombard.layout')

@section('title', 'Главная')

@section('content')
{{-- Hero --}}
<section class="hero-lombard py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold mb-4">Займы под залог без отказов</h1>
                <p class="lead mb-4">Быстрая оценка, выгодные условия и сохранность ваших вещей. Деньги в день обращения.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('cabinet.login') }}" class="btn btn-lombard btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Вход в личный кабинет</a>
                    <a href="#contact" class="btn btn-outline-light btn-lg">Связаться с нами</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Услуги / Что принимаем --}}
<section id="services" class="py-5 bg-white">
    <div class="container py-4">
        <h2 class="section-title mb-5">Наши услуги</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-dark bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-gem fs-3 text-dark"></i>
                        </div>
                        <h5 class="card-title fw-bold">Ювелирные изделия</h5>
                        <p class="card-text small text-muted">Золото, серебро, драгоценные камни. Оценка за 15 минут.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-dark bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-laptop fs-3 text-dark"></i>
                        </div>
                        <h5 class="card-title fw-bold">Техника</h5>
                        <p class="card-text small text-muted">Ноутбуки, телефоны, планшеты, бытовая техника.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-dark bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-clock-history fs-3 text-dark"></i>
                        </div>
                        <h5 class="card-title fw-bold">Часы</h5>
                        <p class="card-text small text-muted">Швейцарские и премиальные часы. Честная оценка.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="rounded-circle bg-dark bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-box-seam fs-3 text-dark"></i>
                        </div>
                        <h5 class="card-title fw-bold">Другие ценности</h5>
                        <p class="card-text small text-muted">Антиквариат, меха, инструменты. Уточняйте по телефону.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Как это работает --}}
<section id="how" class="py-5" style="background: var(--lombard-bg, #f8f8f8);">
    <div class="container py-4">
        <h2 class="section-title mb-5">Как это работает</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex">
                    <span class="flex-shrink-0 rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold me-3" style="width: 48px; height: 48px;">1</span>
                    <div>
                        <h5 class="fw-bold">Принесите вещь</h5>
                        <p class="text-muted small mb-0">Приходите с паспортом и вещью, которую хотите сдать в залог.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex">
                    <span class="flex-shrink-0 rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold me-3" style="width: 48px; height: 48px;">2</span>
                    <div>
                        <h5 class="fw-bold">Оценка и договор</h5>
                        <p class="text-muted small mb-0">Эксперт оценивает залог. Заключаем договор залога, вы получаете деньги.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex">
                    <span class="flex-shrink-0 rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold me-3" style="width: 48px; height: 48px;">3</span>
                    <div>
                        <h5 class="fw-bold">Выкуп в удобный срок</h5>
                        <p class="text-muted small mb-0">Погашаете займ и проценты — залог возвращается. Сроки гибкие.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Преимущества --}}
<section class="py-5 bg-white">
    <div class="container py-4">
        <h2 class="section-title mb-5">Почему мы</h2>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3 d-flex align-items-center">
                <i class="bi bi-check2-circle text-success fs-4 me-2"></i>
                <span>Деньги в день обращения</span>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-center">
                <i class="bi bi-check2-circle text-success fs-4 me-2"></i>
                <span>Честная оценка</span>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-center">
                <i class="bi bi-check2-circle text-success fs-4 me-2"></i>
                <span>Безопасное хранение</span>
            </div>
            <div class="col-sm-6 col-lg-3 d-flex align-items-center">
                <i class="bi bi-check2-circle text-success fs-4 me-2"></i>
                <span>Личный кабинет онлайн</span>
            </div>
        </div>
    </div>
</section>

{{-- Контакты --}}
<section id="contact" class="py-5" style="background: var(--lombard-bg, #f8f8f8);">
    <div class="container py-4 text-center">
        <h2 class="section-title mb-4">Контакты</h2>
        <p class="lead mb-2">Звоните или заходите в личный кабинет</p>
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('services.lombard.phone')) }}" class="fs-3 fw-bold text-dark text-decoration-none d-inline-block mb-3">{{ config('services.lombard.phone') }}</a>
        <div class="mt-3">
            <a href="{{ route('cabinet.login') }}" class="btn btn-lombard"><i class="bi bi-box-arrow-in-right me-1"></i> Вход в личный кабинет</a>
        </div>
    </div>
</section>
@endsection
