@extends('layouts.app')

@section('title', 'Панель управления')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Панель управления</h3>
                </div>
                <div class="card-body">
                    <p>Добро пожаловать, {{ Auth::user()->name }}! Вы вошли в систему.</p>
                    <a href="{{ route('items.index') }}" class="btn btn-primary mt-3">Перейти к объектам</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
