@extends('layouts.app')

@section('title', 'Редактировать награду')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil me-2"></i>Редактировать награду
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('moderation.achievements.update', $achievement) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Название награды</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $achievement->name) }}" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (уникальный идентификатор)</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $achievement->slug) }}" required>
                            <small class="form-text text-muted">Используйте латинские буквы, цифры и дефис. Например: activist, newbie-100</small>
                            @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description', $achievement->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="icon" class="form-label">Иконка награды</label>
                            @if($achievement->icon)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $achievement->icon) }}" alt="{{ $achievement->name }}" style="max-width: 128px; max-height: 128px;" class="border rounded">
                                <p class="small text-muted mt-1">Текущая иконка</p>
                            </div>
                            @endif
                            <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon" accept="image/png,image/jpeg,image/jpg,image/gif,image/svg+xml,image/webp">
                            <small class="form-text text-muted">Рекомендуемый размер: 128x128px. Форматы: PNG, JPG, GIF, SVG, WEBP. Максимум: 2MB. Оставьте пустым, чтобы не менять.</small>
                            @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Тип награды</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="auto" {{ old('type', $achievement->type) === 'auto' ? 'selected' : '' }}>Автоматическая</option>
                                <option value="manual" {{ old('type', $achievement->type) === 'manual' ? 'selected' : '' }}>Ручная</option>
                            </select>
                            <small class="form-text text-muted">Автоматические награды выдаются системой при выполнении условий. Ручные выдаются модераторами.</small>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="auto-conditions" style="{{ old('type', $achievement->type) === 'manual' ? 'display: none;' : '' }}">
                            <div class="mb-3">
                                <label for="condition_type" class="form-label">Тип условия</label>
                                <select class="form-select @error('condition_type') is-invalid @enderror" id="condition_type" name="condition_type">
                                    <option value="">Выберите тип условия</option>
                                    <option value="posts_count" {{ old('condition_type', $achievement->condition_type) === 'posts_count' ? 'selected' : '' }}>Количество постов</option>
                                    <option value="topics_count" {{ old('condition_type', $achievement->condition_type) === 'topics_count' ? 'selected' : '' }}>Количество тем</option>
                                    <option value="days_active" {{ old('condition_type', $achievement->condition_type) === 'days_active' ? 'selected' : '' }}>Дней с регистрации</option>
                                </select>
                                @error('condition_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="condition_value" class="form-label">Значение условия</label>
                                <input type="number" class="form-control @error('condition_value') is-invalid @enderror" id="condition_value" name="condition_value" min="1" value="{{ old('condition_value', $achievement->condition_value) }}">
                                <small class="form-text text-muted">Например: 100 (для 100 постов), 10 (для 10 тем), 30 (для 30 дней)</small>
                                @error('condition_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="display_order" class="form-label">Порядок отображения</label>
                            <input type="number" class="form-control @error('display_order') is-invalid @enderror" id="display_order" name="display_order" min="0" value="{{ old('display_order', $achievement->display_order) }}">
                            <small class="form-text text-muted">Меньшее число = выше в списке.</small>
                            @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" {{ old('is_active', $achievement->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Награда активна
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('moderation.achievements.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Отмена
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Пользователи с этой наградой ({{ $achievement->users()->count() }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($achievement->users()->count() > 0)
                    <div class="list-group">
                        @foreach($achievement->users()->limit(10)->get() as $user)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('profile.show', $user) }}" class="text-decoration-none">{{ $user->name }}</a>
                                <br>
                                <small class="text-muted">Получена: {{ $user->pivot->awarded_at }}</small>
                            </div>
                            <form action="{{ route('moderation.achievements.revoke', $achievement) }}" method="POST" class="d-inline" onsubmit="return confirm('Отозвать награду у этого пользователя?')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-x-lg"></i> Отозвать
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @if($achievement->users()->count() > 10)
                    <p class="text-muted small mt-2">Показаны первые 10 пользователей из {{ $achievement->users()->count() }}</p>
                    @endif
                    @else
                    <p class="text-muted">Эта награда пока никому не выдана.</p>
                    @endif
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
</script>
@endsection
