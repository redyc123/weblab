@extends('layouts.app')

@section('title', 'Удаленные элементы')

@section('content')
<div class="container">
    <h1>Удаленные элементы</h1>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Описание</th>
                    <th>Цена</th>
                    <th>Дата выпуска</th>
                    <th>Категория</th>
                    <th>Пользователь</th>
                    <th>Дата удаления</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($item->description, 50) }}</td>
                        <td>{{ $item->price }}</td>
                        <td>{{ $item->released_at_formatted }}</td>
                        <td>{{ $item->category }}</td>
                        <td>{{ $item->user->name ?? 'N/A' }}</td>
                        <td>{{ $item->deleted_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('items.restore', $item->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Восстановить">
                                    Восстановить
                                </button>
                            </form>
                            <form method="POST" action="{{ route('items.forceDestroy.post', $item) }}" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите навсегда удалить этот элемент?')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger" title="Удалить навсегда">
                                    Удалить навсегда
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">Нет удаленных элементов</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
    
    <div class="mt-3">
        <a href="{{ route('items.index') }}" class="btn btn-secondary">Назад к списку</a>
    </div>
</div>
@endsection