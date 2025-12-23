@extends('layouts.guest')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Регистрация</h3>
    </div>
    <div class="card-body">
        <!-- Validation Errors -->
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Имя</label>
                <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus />
            </div>

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required />
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required />
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a class="btn btn-link" href="{{ route('login') }}">
                    Уже зарегистрированы?
                </a>

                <button type="submit" class="btn btn-primary">
                    Зарегистрироваться
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
