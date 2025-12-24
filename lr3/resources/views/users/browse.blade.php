@extends('layouts.app')

@section('title', 'Просмотр пользователей')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Все пользователи</h1>
</div>

<div class="row">
    @foreach($users as $user)
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">{{ $user->name }}</h5>
                <p class="card-text"><strong>Имя пользователя:</strong> {{ $user->username }}</p>
                <p class="card-text"><strong>Email:</strong> {{ $user->email }}</p>
                
                <div class="mt-auto">
                    <a href="{{ route('users.items', $user) }}" class="btn btn-primary btn-sm">Просмотреть страницу</a>
                    
                    <form method="POST" action="{{ route('users.toggle-friendship', $user) }}" class="d-inline ms-2">
                        @csrf
                        <button type="submit" class="btn btn-{{ $user->is_friend ? 'warning' : 'primary' }} btn-sm">
                            {{ $user->is_friend ? 'Удалить из друзей' : 'Добавить в друзья' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection