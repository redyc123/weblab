@extends('layouts.guest')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Подтверждение пароля</h3>
    </div>
    <div class="card-body">
        <div class="mb-4 text-muted">
            Это защищенная область приложения. Пожалуйста, подтвердите свой пароль перед продолжением.
        </div>

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

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                    Подтвердить
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
