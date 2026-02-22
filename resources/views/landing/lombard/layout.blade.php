<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Главная') — {{ config('services.lombard.name', 'Ломбард') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --lombard-dark: #1a1a1a;
            --lombard-gold: #c9a227;
            --lombard-gold-hover: #b8921f;
            --lombard-bg: #f8f8f8;
        }
        body {
            font-family: 'Montserrat', system-ui, sans-serif;
            color: var(--lombard-dark);
            -webkit-text-size-adjust: 100%;
        }
        .navbar-lombard {
            background: var(--lombard-dark);
        }
        .navbar-lombard .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
        }
        .btn-lombard {
            background: var(--lombard-gold);
            border: none;
            color: var(--lombard-dark);
            font-weight: 600;
        }
        .btn-lombard:hover {
            background: var(--lombard-gold-hover);
            color: var(--lombard-dark);
        }
        .section-title {
            font-weight: 700;
            color: var(--lombard-dark);
        }
        .hero-lombard {
            min-height: 70vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--lombard-dark) 0%, #2d2d2d 100%);
            color: #fff;
        }
        .hero-lombard .lead { opacity: 0.9; }
        footer.footer-lombard {
            background: var(--lombard-dark);
            color: rgba(255,255,255,0.85);
        }
        footer.footer-lombard a { color: var(--lombard-gold); }
        footer.footer-lombard a:hover { color: var(--lombard-gold-hover); }
        .nav-link {
            font-weight: 500;
        }
        @media (max-width: 991.98px) {
            .navbar .navbar-toggler { border-color: rgba(255,255,255,.5); }
            .navbar .nav-link { padding: 0.75rem 1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-lombard py-3">
        <div class="container">
            <a class="navbar-brand text-white text-decoration-none" href="{{ route('home') }}">{{ config('services.lombard.name', 'Ломбард') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLombard" aria-controls="navbarLombard" aria-expanded="false" aria-label="Меню">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarLombard">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link text-white" href="{{ url('/') }}">Главная</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ url('/') }}#services">Услуги</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ url('/') }}#how">Как это работает</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="{{ url('/') }}#contact">Контакты</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-lombard btn-sm" href="{{ route('cabinet.login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Личный кабинет</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
        @yield('content')
    </main>
    <footer class="footer-lombard py-4 mt-auto">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <strong>{{ config('services.lombard.name', 'Ломбард') }}</strong>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('services.lombard.phone')) }}" class="text-decoration-none fw-bold">{{ config('services.lombard.phone') }}</a>
                    <span class="mx-2">|</span>
                    <a href="{{ route('cabinet.login') }}" class="btn btn-outline-warning btn-sm">Личный кабинет</a>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
