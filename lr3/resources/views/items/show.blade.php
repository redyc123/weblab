@extends('layouts.app')

@section('title', $item->title)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h3>{{ $item->title }}</h3>
                    @if($item->image)
                        <img src="{{ asset('storage/' . $item->image) }}" class="img-fluid mb-3" style="max-height:400px;object-fit:cover;">
                    @endif
                    <p>{{ $item->description }}</p>
                    <p><strong>Цена:</strong> {{ $item->price ?? '—' }}</p>
                    <p><strong>Релиз:</strong> {{ $item->released_at_formatted ?? '—' }}</p>
                    <p><strong>Категория:</strong> {{ $item->category ?? '—' }}</p>
                    <p>
                        <small class="text-muted">Пользователь: {{ $item->user->name ?? 'N/A' }}</small>
                        @if($item->user_id != Auth::id() && Auth::user()->following->contains($item->user->id))
                            <span class="badge bg-success ms-2">Друг</span>
                        @endif
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('items.index') }}" class="btn btn-secondary">Назад к списку</a>

                        @if($item->user_id === Auth::id() || Auth::user()->is_admin)
                            <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">Редактировать</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Comments section -->
            <div class="card">
                <div class="card-header">
                    <h5>Комментарии</h5>
                </div>
                <div class="card-body">
                    @forelse($item->comments as $comment)
                        <div class="card mb-2" style="font-size: 0.9em;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $comment->user->name }}</strong>
                                    <small class="text-muted">{{ $comment->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                                @if($comment->user_id != Auth::id() && Auth::user()->following->contains($comment->user->id))
                                    <span class="badge bg-success">Друг</span>
                                @endif
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
    </div>
</div>
@endsection
