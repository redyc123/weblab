@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Лента</h2>
            <p>Последние элементы от ваших друзей и вас.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            @foreach($items as $item)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            @if($item->user->id != Auth::id() && Auth::user()->following->contains($item->user->id))
                                <span class="badge bg-success">Друг</span>
                            @endif
                            <a href="{{ route('users.items', $item->user->username) }}">{{ $item->user->name }}</a>
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">{{ $item->created_at->format('d.m.Y H:i') }}</h6>

                        <h4>{{ $item->title }}</h4>

                        @if($item->image)
                            <img src="{{ asset('storage/' . $item->image) }}" class="card-img-top" alt="{{ $item->title }}" style="max-height: 200px; object-fit: cover;">
                        @endif

                        <p class="card-text">{{ $item->description }}</p>

                        <p class="card-text">
                            <small class="text-muted">Цена: ${{ number_format($item->price, 2) }}</small>
                            @if($item->category)
                                <small class="text-muted"> | Категория: {{ $item->category }}</small>
                            @endif
                            @if($item->released_at)
                                <small class="text-muted"> | Релиз: {{ $item->getReleasedAtFormattedAttribute() }}</small>
                            @endif
                        </p>

                        <!-- Comments section -->
                        <div class="comments mt-3">
                            <h6>Комментарии:</h6>
                            @forelse($item->comments as $comment)
                                <div class="card mb-2" style="font-size: 0.9em;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <strong>{{ $comment->user->name }}</strong>
                                            <small class="text-muted">{{ $comment->created_at->format('d.m.Y H:i') }}</small>
                                        </div>
                                        <p class="card-text mb-1">{{ $comment->content }}</p>
                                        @if($comment->user_id == Auth::id() || Auth::user()->is_admin)
                                            <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите удалить этот комментарий?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">Комментариев пока нет.</p>
                            @endforelse

                            <!-- Add comment form -->
                            <form method="POST" action="{{ route('comments.store') }}">
                                @csrf
                                <div class="input-group mb-3">
                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                    <input type="text" class="form-control" name="content" placeholder="Добавить комментарий..." required>
                                    <button class="btn btn-primary" type="submit">Комментировать</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-center">
                {{ $items->links() }}
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Друзья</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse(Auth::user()->following as $friend)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('users.items', $friend->username) }}">{{ $friend->name }}</a>
                                <form method="POST" action="{{ route('users.toggle-friendship', $friend) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Удалить из друзей</button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item">Нет друзей.</li>
                        @endforelse
                    </ul>

                    <div class="mt-3">
                        <h6>Добавить друга</h6>
                        <form method="GET" action="{{ route('users.index') }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Просмотреть пользователей</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection