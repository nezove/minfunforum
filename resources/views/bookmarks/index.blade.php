@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 fw-bold">
                <i class="bi bi-bookmark me-2"></i>Мои закладки
            </h1>
            <span class="badge bg-primary fs-6">{{ $bookmarks->total() }} тем</span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                @forelse($bookmarks as $bookmark)
                <div class="p-4 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-start">
                                <img src="{{ $bookmark->topic->user->avatar_url }}" class="rounded-circle me-3"
                                    alt="Avatar" style="width: 48px; height: 48px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-1 fw-semibold">
                                        <a href="{{ route('topics.show', $bookmark->topic->id) }}"
                                            class="text-decoration-none text-dark">
                                            @if($bookmark->topic->is_pinned)
                                            <i class="bi bi-pin-angle text-warning me-1"></i>
                                            @endif
                                            @if($bookmark->topic->is_locked)
                                            <i class="bi bi-lock text-danger me-1"></i>
                                            @endif
                                            {{ $bookmark->topic->title }}
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        в <a href="{{ route('forum.category', $bookmark->topic->category->id) }}"
                                            class="text-decoration-none">{{ $bookmark->topic->category->name }}</a>
                                        • <i class="bi bi-person me-1"></i>{{ $bookmark->topic->user->name }}
                                        • <i
                                            class="bi bi-clock me-1"></i>{{ $bookmark->topic->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-center">
                            <div class="small">
                                <div class="fw-bold text-primary">{{ $bookmark->topic->replies_count }}</div>
                                <div class="text-muted">ответов</div>
                            </div>
                        </div>
                        <div class="col-md-1 text-center">
                            <div class="small">
                                <div class="fw-bold text-info">{{ $bookmark->topic->views }}</div>
                                <div class="text-muted">просмотров</div>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <!-- Кнопка удаления из закладок -->
                            <button class="btn btn-outline-warning btn-sm" data-topic-id="{{ $bookmark->topic->id }}"
                                data-bs-toggle="tooltip" title="Удалить из закладок" onclick="removeBookmark(this)">
                                <i class="bi bi-bookmark-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-bookmark text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Нет закладок</h5>
                    <p class="text-muted">Добавляйте интересные темы в закладки, чтобы легко находить их позже</p>
                    <a href="{{ route('forum.index') }}" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Найти интересные темы
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Пагинация -->
        @if($bookmarks->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $bookmarks->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Функция удаления из закладок
function removeBookmark(button) {
    const topicId = button.dataset.topicId;
    const topicItem = button.closest('.p-4.border-bottom');

    if (confirm('Удалить тему из закладок?')) {
        fetch(`/topics/${topicId}/bookmark`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.bookmarked) {
                    showToast('success', 'Готово!', 'Закладка удалена');
                    // Анимация удаления
                    topicItem.style.transition = 'opacity 0.3s ease';
                    topicItem.style.opacity = '0';
                    setTimeout(() => {
                        topicItem.remove();

                        // Проверяем, остались ли закладки
                        const remainingItems = document.querySelectorAll('.p-4.border-bottom');
                        if (remainingItems.length === 0) {
                            location.reload(); // Перезагружаем страницу для показа пустого состояния
                        }
                    }, 300);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Ошибка!', 'Произошла ошибка при удалении закладки');
            });
    }
}
</script>
@endsection