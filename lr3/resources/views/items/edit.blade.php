@extends('layouts.app')

@section('title', 'Редактировать объект')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Редактировать: {{ $item->title }}</h5>
        <form action="{{ route('items.update', $item) }}" method="post" enctype="multipart/form-data" novalidate>
            @method('PUT')
            @include('items._form')
        </form>
    </div>
</div>
@endsection
