@extends('layouts.app')

@section('title', 'Список объектов')

@section('content')
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
                <p class="mb-3"><strong>Релиз:</strong> {{ $item->released_at ? $item->released_at->format('d.m.Y') : '—' }}</p>

                <div class="mt-auto">
                    <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal{{ $item->id }}">Подробнее</a>
                    <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary btn-sm">Редактировать</a>

                    <form action="{{ route('items.destroy', $item) }}" method="post" class="d-inline"
                          onsubmit="return confirm('Удалить объект?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('items._modal', ['item' => $item])
    @endforeach
</div>

{{ $items->links() }}
@endsection
