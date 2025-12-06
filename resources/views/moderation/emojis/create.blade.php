@extends('layouts.app')

@section('title', 'Добавить смайлик')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-plus-circle text-primary me-2"></i>
                    Добавить смайлик
                </h1>
                <a href="{{ route('moderation.emojis.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Назад
                </a>
            </div>

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('moderation.emojis.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Превью -->
                        <div class="mb-4 text-center">
                            <div class="border rounded p-3 d-inline-block" style="min-width: 200px;">
                                <div id="previewContainer" style="display: none;">
                                    <img id="emojiPreview" src="" alt="Превью" style="max-width: 100px; max-height: 100px;">
                                </div>
                                <div id="placeholderText" class="text-muted">
                                    <i class="bi bi-image fs-1 d-block mb-2"></i>
                                    Выберите файл для превью
                                </div>
                            </div>
                        </div>

                        <!-- Файл смайлика -->
                        <div class="mb-3">
                            <label for="file" class="form-label fw-semibold">
                                Файл смайлика <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror"
                                   id="file" name="file" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" required>
                            <small class="text-muted">
                                Допустимые форматы: PNG, JPG, GIF, SVG, WEBP. Максимум 2MB.
                            </small>
                            @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Название -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">
                                Название <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="Например: Улыбка">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Категория -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-semibold">
                                Категория <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    id="category_id" name="category_id" required>
                                <option value="">Выберите категорию</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->icon }} {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Ключевые слова -->
                        <div class="mb-3">
                            <label for="keywords" class="form-label fw-semibold">
                                Ключевые слова <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('keywords') is-invalid @enderror"
                                   id="keywords" name="keywords" value="{{ old('keywords') }}" required
                                   placeholder="радость, счастье, улыбка">
                            <small class="text-muted">
                                Введите слова через запятую. По этим словам пользователи смогут найти смайлик.
                            </small>
                            @error('keywords')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Размеры -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="width" class="form-label fw-semibold">Ширина (px)</label>
                                <input type="number" class="form-control @error('width') is-invalid @enderror"
                                       id="width" name="width" value="{{ old('width', 24) }}"
                                       min="16" max="128" placeholder="24">
                                <small class="text-muted">От 16 до 128 пикселей</small>
                                @error('width')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="height" class="form-label fw-semibold">Высота (px)</label>
                                <input type="number" class="form-control @error('height') is-invalid @enderror"
                                       id="height" name="height" value="{{ old('height', 24) }}"
                                       min="16" max="128" placeholder="24">
                                <small class="text-muted">От 16 до 128 пикселей</small>
                                @error('height')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Активность -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active"
                                       name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Смайлик активен
                                </label>
                            </div>
                            <small class="text-muted">Неактивные смайлики не будут отображаться пользователям</small>
                        </div>

                        <!-- Кнопки -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>Добавить смайлик
                            </button>
                            <a href="{{ route('moderation.emojis.index') }}" class="btn btn-outline-secondary">
                                Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('emojiPreview').src = e.target.result;
            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('placeholderText').style.display = 'none';
        };
        reader.readAsDataURL(file);

        // Автоматически получаем размеры изображения
        const img = new Image();
        img.onload = function() {
            document.getElementById('width').value = this.width;
            document.getElementById('height').value = this.height;
        };
        img.src = URL.createObjectURL(file);
    }
});
</script>
@endsection
