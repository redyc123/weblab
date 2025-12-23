<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'LR3 Laravel')</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-light bg-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('items.index') }}">ТУРЫ</a>
        <div class="navbar-nav ms-auto">
            @auth
                <span class="navbar-text me-3">Привет, {{ Auth::user()->name }}!</span>
                @if(Auth::user()->is_admin)
                    <span class="badge bg-warning me-3">ADMIN</span>
                @endif
                <a class="btn btn-outline-secondary me-2" href="{{ route('dashboard') }}">Панель</a>
                <a class="btn btn-outline-primary me-2" href="{{ route('items.create') }}">Добавить</a>
                @if(Auth::user()->is_admin)
                    <a class="btn btn-outline-info me-2" href="{{ route('users.index') }}">Пользователи</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">Выйти</button>
                </form>
            @else
                <a class="btn btn-outline-primary me-2" href="{{ route('login') }}">Войти</a>
                <a class="btn btn-outline-secondary" href="{{ route('register') }}">Регистрация</a>
            @endauth
        </div>
    </div>
</nav>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</div>

<script src="{{ mix('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>