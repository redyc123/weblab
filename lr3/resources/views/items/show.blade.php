@extends('layouts.app')

@section('title', $item->title)

@section('content')
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
        @if(Auth::user()->is_admin)
            <p><small class="text-muted">Пользователь: {{ $item->user->name ?? 'N/A' }}</small></p>
        @endif

        <div class="mt-3">
            <a href="{{ route('items.index') }}" class="btn btn-secondary">Назад к списку</a>

            @if($item->user_id === Auth::id() || Auth::user()->is_admin)
                <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">Редактировать</a>
            @endif
        </div>
    </div>
</div>
@endsection
