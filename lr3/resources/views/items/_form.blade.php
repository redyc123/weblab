@csrf

<div class="mb-3">
    <label for="title" class="form-label">Название</label>
    <input id="title" name="title" type="text" class="form-control" required value="{{ old('title', $item->title ?? '') }}">
</div>

<div class="mb-3">
    <label for="description" class="form-label">Описание</label>
    <textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $item->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label for="price" class="form-label">Цена</label>
    <input id="price" name="price" type="number" step="0.01" min="0" class="form-control" value="{{ old('price', $item->price ?? '') }}">
</div>

<div class="mb-3">
    <label for="released_at" class="form-label">Дата релиза</label>
    <input id="released_at" name="released_at" type="date" class="form-control" value="{{ old('released_at', isset($item) ? $item->released_at->format('Y-m-d') : '') }}">
</div>

<div class="mb-3">
    <label for="category" class="form-label">Категория</label>
    <input id="category" name="category" type="text" class="form-control" value="{{ old('category', $item->category ?? '') }}">
</div>

<div class="mb-3">
    <label for="image" class="form-label">Изображение</label>
    <input id="image" name="image" type="file" accept="image/*" class="form-control">
    @if(!empty($item->image ?? null))
        <div class="mt-2">
            <img src="{{ asset('storage/' . $item->image) }}" alt="img" style="max-width:200px;">
        </div>
    @endif
</div>

<button type="submit" class="btn btn-success">Сохранить</button>
<a href="{{ route('items.index') }}" class="btn btn-secondary">Отмена</a>
