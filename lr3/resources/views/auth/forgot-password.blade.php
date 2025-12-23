@extends('layouts.guest')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Восстановление пароля</h3>
    </div>
    <div class="card-body">
        <div class="mb-4 text-muted">
            Забыли пароль? Нет проблем. Сообщите нам свой адрес электронной почты, и мы вышлем вам ссылку для сброса пароля, которая позволит вам выбрать новый.
        </div>

        <!-- Session Status -->
        @if(session('status'))
            <div class="alert alert-success mb-4">
                {{ session('status') }}
            </div>
        @endif

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

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus />
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    Отправить ссылку для сброса
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
