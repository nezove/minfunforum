@extends('layouts.app')

@section('title', 'Создать награду')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-trophy me-2"></i>Создать новую награду
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('moderation.achievements.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Название награды</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (уникальный идентификатор)</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}" required>
                            <small class="form-text text-muted">Используйте латинские буквы, цифры и дефис. Например: activist, newbie-100</small>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="icon" class="form-label">Иконка награды</label>
                            <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon" accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml,image/webp">
                            <small class="form-text text-muted">Рекомендуемый размер: 128x128px. Форматы: PNG, JPG, GIF, SVG, WEBP. Максимум: 2MB</small>
                            @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Тип награды</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="auto" {{ old('type') === 'auto' ? 'selected' : '' }}>Автоматическая</option>
                                <option value="manual" {{ old('type') === 'manual' ? 'selected' : '' }}>Ручная</option>
                            </select>
                            <small class="form-text text-muted">Автоматические награды выдаются системой при выполнении условий. Ручные выдаются модераторами.</small>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="auto-conditions" style="{{ old('type', 'auto') === 'manual' ? 'display: none;' : '' }}">
                            <div class="mb-3">
                                <label for="condition_type" class="form-label">Тип условия</label>
                                <select class="form-select @error('condition_type') is-invalid @enderror" id="condition_type" name="condition_type">
                                    <option value="">Выберите тип условия</option>
                                    <option value="posts_count" {{ old('condition_type') === 'posts_count' ? 'selected' : '' }}>Количество постов</option>
                                    <option value="topics_count" {{ old('condition_type') === 'topics_count' ? 'selected' : '' }}>Количество тем</option>
                                    <option value="days_active" {{ old('condition_type') === 'days_active' ? 'selected' : '' }}>Дней с регистрации</option>
                                </select>
                                @error('condition_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="condition_value" class="form-label">Значение условия</label>
                                <input type="number" class="form-control @error('condition_value') is-invalid @enderror" id="condition_value" name="condition_value" min="1" value="{{ old('condition_value') }}">
                                <small class="form-text text-muted">Например: 100 (для 100 постов), 10 (для 10 тем), 30 (для 30 дней)</small>
                                @error('condition_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="display_order" class="form-label">Порядок отображения</label>
                            <input type="number" class="form-control @error('display_order') is-invalid @enderror" id="display_order" name="display_order" min="0" value="{{ old('display_order', 0) }}">
                            <small class="form-text text-muted">Меньшее число = выше в списке. Оставьте 0 для автоматической установки.</small>
                            @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Награда активна
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('moderation.achievements.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Отмена
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Создать награду
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const autoConditions = document.getElementById('auto-conditions');
    if (this.value === 'manual') {
        autoConditions.style.display = 'none';
        document.getElementById('condition_type').value = '';
        document.getElementById('condition_value').value = '';
    } else {
        autoConditions.style.display = 'block';
    }
});

document.getElementById('name').addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
});
</script>
@endsection
