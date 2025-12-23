@extends('layouts.guest')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Подтверждение email</h3>
    </div>
    <div class="card-body">
        <div class="mb-4 text-muted">
            Спасибо за регистрацию! Перед началом работы, пожалуйста, подтвердите свой адрес электронной почты, перейдя по ссылке, которую мы только что отправили вам. Если вы не получили письмо, мы с радостью вышлем вам еще одно.
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success mb-4">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mt-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <button type="submit" class="btn btn-primary">
                    Повторно отправить письмо подтверждения
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf

                <button type="submit" class="btn btn-link text-muted">
                    Выйти
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
