@extends('layouts.app')

@section('title', $user->name . ' - Объекты')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Объекты пользователя {{ $user->name }}</h1>
    <div>
        @if(Auth::check())
            @php
                $is_friend = Auth::user()->following()->where('friend_id', $user->id)->exists();
            @endphp
            <form method="POST" action="{{ route('users.toggle-friendship', $user) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-{{ $is_friend ? 'warning' : 'primary' }}">
                    {{ $is_friend ? 'Удалить из друзей' : 'Добавить в друзья' }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row">
    @foreach($items as $item)
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            @if($item->image)
                <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" style="height:200px;object-fit:cover;">
            @endif
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">{{ $item->title }}</h5>
                <p class="card-text text-truncate">{{ $item->description }}</p>
                <p class="mb-1"><strong>Цена:</strong> {{ $item->price ?? '—' }}</p>
                <p class="mb-1"><strong>Релиз:</strong> {{ $item->released_at_formatted ?? '—' }}</p>
                <p class="mb-3"><strong>Категория:</strong> {{ $item->category ?? '—' }}</p>
                <p class="mb-2"><small class="text-muted">Пользователь: {{ $item->user->name ?? 'N/A' }}</small></p>

                <div class="mt-auto">
                    <a href="{{ route('items.show', $item) }}" class="btn btn-primary btn-sm">Подробнее</a>

                    <!-- Regular users cannot edit or delete other users' items -->
                    <!-- Only admin can manage other users' items -->
                    @auth
                        @if(Auth::user()->is_admin)
                            <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary btn-sm">Редактировать</a>
                            <form action="{{ route('items.destroy', $item) }}" method="post" class="d-inline"
                                  onsubmit="return confirm('Удалить объект?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Удалить</button>
                            </form>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{ $items->links() }}
@endsection