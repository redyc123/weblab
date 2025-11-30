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
        <p><strong>Цена:</strong> {{ $item->price }}</p>
        <p><strong>Релиз:</strong> {{ $item->released_at ? $item->released_at->format('d.m.Y') : '—' }}</p>
    </div>
</div>
@endsection
