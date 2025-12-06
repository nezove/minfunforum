@extends('layouts.app')

@section('title', 'Управление категориями и тегами')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-folder text-primary me-2"></i>
                    Управление категориями и тегами
                </h1>
                <div>
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                        <i class="bi bi-plus-circle me-1"></i>Создать категорию
                    </button>
                    <a href="{{ route('moderation.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Назад к панели
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Список категорий -->
            <div class="row">
                @foreach($categories as $category)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                @if($category->icon)
                                    <i class="bi bi-{{ $category->icon }} me-2"></i>
                                @endif
                                {{ $category->name }}
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" onclick="editCategory({{ $category->id }})">
                                            <i class="bi bi-pencil me-2"></i>Редактировать
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" onclick="createTag({{ $category->id }}, '{{ $category->name }}')">
                                            <i class="bi bi-tag me-2"></i>Добавить тег
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('moderation.categories.delete', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить категорию? Это действие нельзя отменить!')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Удалить
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">{{ $category->description ?: 'Нет описания' }}</p>
                            
<div class="mb-3">
    <strong>Тем:</strong> <span class="badge bg-primary">{{ $category->topics_count }}</span>
    <strong class="ms-2">Постов:</strong> <span class="badge bg-info">{{ $category->posts_count }}</span>
    @if($category->allow_gallery)
        <strong class="ms-2">Галерея:</strong> <span class="badge bg-success">включена</span>
    @endif
</div>

                            <!-- Теги категории -->
                            <div class="mb-2">
                                <strong class="small">Теги ({{ $category->tags->count() }}):</strong>
                            </div>
                            @if($category->tags->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($category->tags as $tag)
                                        <div class="position-relative">
                                            <span class="badge d-inline-flex align-items-center" 
                                                  style="background-color: {{ $tag->color }}; color: white; padding-right: 25px;">
                                                {{ $tag->name }}
                                                @if($tag->topics_count > 0)
                                                    <small class="ms-1">({{ $tag->topics_count }})</small>
                                                @endif
                                                @if(!$tag->is_active)
                                                    <small class="ms-1 opacity-50">[неактивен]</small>
                                                @endif
                                            </span>
                                            <button type="button" 
                                                    class="btn btn-sm position-absolute top-0 end-0 p-0 border-0 bg-transparent" 
                                                    style="width: 20px; height: 20px; line-height: 1; color: white; font-size: 12px;"
                                                    onclick="editTag({{ $tag->id }})"
                                                    title="Редактировать тег">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted small">Нет тегов</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно создания категории -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('moderation.categories.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Создать категорию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Название категории <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Иконка (Bootstrap Icons)</label>
                                <input type="text" name="icon" class="form-control" placeholder="Например: code-slash">
                                <small class="text-muted">Список иконок: <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Порядок сортировки</label>
                        <input type="number" name="sort_order" class="form-control" min="0">
                    </div>
<div class="mb-3">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="allow_gallery" name="allow_gallery" value="1">
        <label class="form-check-label" for="allow_gallery">
            <strong>Разрешить галерею изображений</strong>
            <small class="text-muted d-block">Пользователи смогут загружать до 20 изображений при создании темы</small>
        </label>
    </div>
</div>

                    <!-- SEO настройки -->
                    <h6 class="mt-4 mb-3">SEO настройки</h6>
                    <div class="mb-3">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="seo_title" class="form-control" maxlength="255">
                        <small class="text-muted">Если не заполнено, будет сгенерировано автоматически</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Description</label>
                        <textarea name="seo_description" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" class="form-control" maxlength="500">
                        <small class="text-muted">Ключевые слова через запятую</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Создать категорию</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования категории -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать категорию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Название категории <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_category_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Иконка (Bootstrap Icons)</label>
                                <input type="text" name="icon" id="edit_category_icon" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" id="edit_category_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Порядок сортировки</label>
                        <input type="number" name="sort_order" id="edit_category_sort_order" class="form-control" min="0">
                    </div>
<div class="mb-3">
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="edit_allow_gallery" name="allow_gallery" value="1">
        <label class="form-check-label" for="edit_allow_gallery">
            <strong>Разрешить галерею изображений</strong>
            <small class="text-muted d-block">Пользователи смогут загружать до 20 изображений при создании темы</small>
        </label>
    </div>
</div>

                    <!-- SEO настройки -->
                    <h6 class="mt-4 mb-3">SEO настройки</h6>
                    <div class="mb-3">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="seo_title" id="edit_category_seo_title" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Description</label>
                        <textarea name="seo_description" id="edit_category_seo_description" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" id="edit_category_seo_keywords" class="form-control" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно создания тега -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('moderation.tags.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createTagModalTitle">Создать тег</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="tag_category_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Название тега <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Цвет тега</label>
                                <input type="color" name="color" class="form-control form-control-color" value="#007bff">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <!-- SEO настройки -->
                    <h6 class="mt-4 mb-3">SEO настройки</h6>
                    <div class="mb-3">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="seo_title" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Description</label>
                        <textarea name="seo_description" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" class="form-control" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Создать тег</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования тега -->
<div class="modal fade" id="editTagModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editTagForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать тег</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Название тега <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_tag_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Цвет тега</label>
                                <input type="color" name="color" id="edit_tag_color" class="form-control form-control-color">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" id="edit_tag_description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_tag_is_active" class="form-check-input" value="1">
                            <label class="form-check-label" for="edit_tag_is_active">
                                Активный тег
                            </label>
                        </div>
                    </div>

                    <!-- SEO настройки -->
                    <h6 class="mt-4 mb-3">SEO настройки</h6>
                    <div class="mb-3">
                        <label class="form-label">SEO Title</label>
                        <input type="text" name="seo_title" id="edit_tag_seo_title" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Description</label>
                        <textarea name="seo_description" id="edit_tag_seo_description" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SEO Keywords</label>
                        <input type="text" name="seo_keywords" id="edit_tag_seo_keywords" class="form-control" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" onclick="deleteTag()">
                        <i class="bi bi-trash me-1"></i>Удалить тег
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Данные категорий для JavaScript
const categories = @json($categories);

// Редактирование категории
// Редактирование категории
function editCategory(categoryId) {
    const category = categories.find(c => c.id === categoryId);
    if (!category) {
        alert('Категория не найдена');
        return;
    }

    document.getElementById('editCategoryForm').action = `/moderation/categories/${categoryId}`;
    document.getElementById('edit_category_name').value = category.name || '';
    document.getElementById('edit_category_icon').value = category.icon || '';
    document.getElementById('edit_category_description').value = category.description || '';
    document.getElementById('edit_category_sort_order').value = category.sort_order || '';
    document.getElementById('edit_category_seo_title').value = category.seo_title || '';
    document.getElementById('edit_category_seo_description').value = category.seo_description || '';
    document.getElementById('edit_category_seo_keywords').value = category.seo_keywords || '';
    
    // ИСПРАВЛЕНО: правильный ID и безопасная проверка
    const galleryCheckbox = document.getElementById('edit_allow_gallery');
    if (galleryCheckbox) {
        galleryCheckbox.checked = category.allow_gallery || false;
    }

    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

// Создание тега
function createTag(categoryId, categoryName) {
    document.getElementById('tag_category_id').value = categoryId;
    document.getElementById('createTagModalTitle').textContent = `Создать тег для категории "${categoryName}"`;
    
    new bootstrap.Modal(document.getElementById('createTagModal')).show();
}

// Редактирование тега
function editTag(tagId) {
    // Найдем тег в данных категорий
    let tag = null;
    for (const category of categories) {
        tag = category.tags.find(t => t.id === tagId);
        if (tag) break;
    }
    
    if (!tag) {
        alert('Тег не найден');
        return;
    }

    document.getElementById('editTagForm').action = `/moderation/tags/${tagId}`;
    document.getElementById('edit_tag_name').value = tag.name || '';
    document.getElementById('edit_tag_color').value = tag.color || '#007bff';
    document.getElementById('edit_tag_description').value = tag.description || '';
    document.getElementById('edit_tag_is_active').checked = tag.is_active;
    document.getElementById('edit_tag_seo_title').value = tag.seo_title || '';
    document.getElementById('edit_tag_seo_description').value = tag.seo_description || '';
    document.getElementById('edit_tag_seo_keywords').value = tag.seo_keywords || '';

    // Сохраняем ID тега для функции удаления
    window.currentEditingTagId = tagId;

    new bootstrap.Modal(document.getElementById('editTagModal')).show();
}

// Удаление тега
function deleteTag() {
    if (!window.currentEditingTagId) {
        alert('Ошибка: ID тега не найден');
        return;
    }

    if (!confirm('Удалить тег? Это действие нельзя отменить!')) return;
    
    // Создаем форму для удаления
    const deleteForm = document.createElement('form');
    deleteForm.method = 'POST';
    deleteForm.action = `/moderation/tags/${window.currentEditingTagId}`;
    deleteForm.style.display = 'none';
    deleteForm.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;
    
    document.body.appendChild(deleteForm);
    deleteForm.submit();
}

// Инициализация всплывающих подсказок
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Предварительный просмотр цвета тега
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.parentElement.querySelector('.color-preview');
            if (preview) {
                preview.style.backgroundColor = this.value;
            }
        });
    });
});

// Функция для подтверждения удаления с дополнительной информацией
function confirmTagDeletion(tagName, topicsCount) {
    let message = `Удалить тег "${tagName}"?`;
    if (topicsCount > 0) {
        message += `\n\nВнимание: этот тег используется в ${topicsCount} темах. После удаления тег будет убран из всех тем.`;
    }
    message += '\n\nЭто действие нельзя отменить!';
    
    return confirm(message);
}
</script>
@endsection