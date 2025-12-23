@extends('layouts.app')

@section('title', 'Пользователи')

@section('content')
<div class="container">
    <h1>Пользователи</h1>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Администратор</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->is_admin)
                                <span class="badge bg-warning">Да</span>
                            @else
                                Нет
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('users.items', $user->username) }}" class="btn btn-sm btn-primary">
                                Просмотреть элементы
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Нет пользователей</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <a href="{{ route('items.index') }}" class="btn btn-secondary">Назад к списку</a>
    </div>
</div>
@endsection