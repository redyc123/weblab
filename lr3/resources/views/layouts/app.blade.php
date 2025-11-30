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
        <a class="navbar-brand" href="{{ route('items.index') }}">LR3</a>
        <div>
            <a class="btn btn-primary" href="{{ route('items.create') }}">Добавить</a>
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
