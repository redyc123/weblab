<div class="modal fade" id="itemModal{{ $item->id }}" tabindex="-1" aria-labelledby="itemModalLabel{{ $item->id }}" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ $item->title }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @if($item->image)
            <img src="{{ asset('storage/' . $item->image) }}" alt="" class="img-fluid mb-3">
        @endif

        <p><strong>Описание:</strong></p>
        <p>{{ $item->description }}</p>

        <p><strong>Цена:</strong> {{ $item->price ?? '—' }}</p>
        <p><strong>Релиз:</strong> {{ $item->released_at ? $item->released_at->format('d.m.Y') : '—' }}</p>
        <p><strong>Категория:</strong> {{ $item->category ?? '—' }}</p>
      </div>
      <div class="modal-footer">
        <a href="{{ route('items.edit', $item) }}" class="btn btn-secondary">Редактировать</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>
