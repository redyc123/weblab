@extends('layouts.app')

@section('title', 'Добавить объект')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Добавить объект</h5>
        <form action="{{ route('items.store') }}" method="post" enctype="multipart/form-data" novalidate>
            @include('items._form')
        </form>
    </div>
</div>
@endsection
