@extends('layouts.app')

@section('title', 'Управление смайликами')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-emoji-smile text-warning me-2"></i>
                    Управление смайликами
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('moderation.emojis.categories') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-folder me-1"></i>Категории
                    </a>
                    <a href="{{ route('moderation.emojis.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Добавить смайлик
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Фильтр по категориям -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select" id="categoryFilter">
                                <option value="">Все категории</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->icon }} {{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" id="statusFilter">
                                <option value="">Все</option>
                                <option value="active">Активные</option>
                                <option value="inactive">Неактивные</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="searchEmoji" placeholder="Поиск по названию...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Список смайликов -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="80">Превью</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Ключевые слова</th>
                                    <th>Размер</th>
                                    <th width="100">Использований</th>
                                    <th width="80">Статус</th>
                                    <th width="150">Действия</th>
                                </tr>
                            </thead>
                            <tbody id="emojisTable">
                                @forelse($emojis as $emoji)
                                <tr data-category="{{ $emoji->category_id }}" data-status="{{ $emoji->is_active ? 'active' : 'inactive' }}" data-name="{{ strtolower($emoji->name) }}">
                                    <td>
                                        <img src="{{ $emoji->file_url }}" alt="{{ $emoji->name }}"
                                             width="{{ $emoji->width }}" height="{{ $emoji->height }}"
                                             class="rounded">
                                    </td>
                                    <td>
                                        <strong>{{ $emoji->name }}</strong><br>
                                        <small class="text-muted">{{ $emoji->file_type }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $emoji->category->icon }} {{ $emoji->category->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($emoji->keywords, 50) }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $emoji->width }}x{{ $emoji->height }}px</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $emoji->usage_count }}</span>
                                    </td>
                                    <td>
                                        @if($emoji->is_active)
                                        <span class="badge bg-success">Активен</span>
                                        @else
                                        <span class="badge bg-secondary">Неактивен</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('moderation.emojis.edit', $emoji) }}"
                                               class="btn btn-outline-primary" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="deleteEmoji({{ $emoji->id }})" title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Смайликов пока нет. <a href="{{ route('moderation.emojis.create') }}">Добавить первый смайлик</a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Пагинация -->
                    <div class="mt-3">
                        {{ $emojis->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Форма удаления -->
<form id="deleteEmojiForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтрация
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchEmoji');
    const rows = document.querySelectorAll('#emojisTable tr[data-category]');

    function filterTable() {
        const category = categoryFilter.value;
        const status = statusFilter.value;
        const search = searchInput.value.toLowerCase();

        rows.forEach(row => {
            const rowCategory = row.dataset.category;
            const rowStatus = row.dataset.status;
            const rowName = row.dataset.name;

            const categoryMatch = !category || rowCategory === category;
            const statusMatch = !status || rowStatus === status;
            const searchMatch = !search || rowName.includes(search);

            row.style.display = (categoryMatch && statusMatch && searchMatch) ? '' : 'none';
        });
    }

    categoryFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
    searchInput.addEventListener('input', filterTable);
});

function deleteEmoji(id) {
    if (!confirm('Вы уверены, что хотите удалить этот смайлик?')) {
        return;
    }

    const form = document.getElementById('deleteEmojiForm');
    form.action = `/moderation/emojis/${id}`;
    form.submit();
}
</script>
@endsection
