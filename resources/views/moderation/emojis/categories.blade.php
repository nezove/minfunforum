@extends('layouts.app')

@section('title', 'Категории смайликов')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-folder text-warning me-2"></i>
                    Категории смайликов
                </h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('moderation.emojis.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>К смайликам
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                        <i class="bi bi-plus-lg me-1"></i>Добавить категорию
                    </button>
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

            <!-- Список категорий -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="80">Иконка</th>
                                    <th>Название</th>
                                    <th>Slug</th>
                                    <th width="100">Смайликов</th>
                                    <th width="80">Статус</th>
                                    <th width="150">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                <tr>
                                    <td class="fs-3">{{ $category->icon }}</td>
                                    <td><strong>{{ $category->name }}</strong></td>
                                    <td><code>{{ $category->slug }}</code></td>
                                    <td>
                                        <span class="badge bg-info">{{ $category->emojis_count }}</span>
                                    </td>
                                    <td>
                                        @if($category->is_active)
                                        <span class="badge bg-success">Активна</span>
                                        @else
                                        <span class="badge bg-secondary">Неактивна</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary"
                                                    onclick="editCategory({{ $category->id }}, '{{ $category->name }}', '{{ $category->icon }}', {{ $category->is_active ? 'true' : 'false' }})"
                                                    title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            @if($category->emojis_count == 0)
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="deleteCategory({{ $category->id }})" title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-outline-secondary" disabled
                                                    title="Нельзя удалить категорию со смайликами">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Категорий пока нет
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания категории -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('moderation.emojis.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Добавить категорию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Название</label>
                        <input type="text" class="form-control" id="create_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_icon" class="form-label">Иконка (эмодзи)</label>
                        <input type="text" class="form-control" id="create_icon" name="icon" placeholder="😀">
                        <small class="text-muted">Вставьте любой эмодзи</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" checked>
                        <label class="form-check-label" for="create_is_active">Активна</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования категории -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать категорию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Название</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_icon" class="form-label">Иконка (эмодзи)</label>
                        <input type="text" class="form-control" id="edit_icon" name="icon">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                        <label class="form-check-label" for="edit_is_active">Активна</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Форма удаления -->
<form id="deleteCategoryForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function editCategory(id, name, icon, isActive) {
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('edit_is_active').checked = isActive;
    document.getElementById('editCategoryForm').action = `/moderation/emojis/categories/${id}`;

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function deleteCategory(id) {
    if (!confirm('Вы уверены, что хотите удалить эту категорию?')) {
        return;
    }

    const form = document.getElementById('deleteCategoryForm');
    form.action = `/moderation/emojis/categories/${id}`;
    form.submit();
}
</script>
@endsection
