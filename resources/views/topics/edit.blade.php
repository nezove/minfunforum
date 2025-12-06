@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h4>Редактировать тему</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('topics.update', $topic->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Поле заголовка -->
                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Заголовок <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title', $topic->title) }}" required>
                        @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Поле категории -->
                    <div class="mb-3">
                        <label for="category_id" class="form-label">
                            Категория <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('category_id') is-invalid @enderror" 
                                id="category_id" name="category_id" required>
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                    {{ old('category_id', $topic->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Поле содержания -->
                    <div class="mb-3">
                        <label for="content" class="form-label">
                            Содержание <span class="text-danger">*</span>
                        </label>

                        <div class="editor-container">
                            <!-- Quill Editor -->
                            <div id="quill-editor" style="min-height: 200px;">{!! old('content', $topic->content) !!}</div>

                            <!-- Скрытое поле для отправки HTML -->
                            <textarea id="content" name="content" style="display: none;" required>{!! old('content', $topic->content) !!}</textarea>

                            <!-- Счетчик символов -->
                            <div class="char-counter" id="char-count">0 символов</div>

                            @error('content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Управление прикрепленными файлами -->
                    @if($topic->files && $topic->files->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-paperclip me-1"></i>Прикрепленные файлы
                        </label>
                        <div class="border rounded p-3">
                            @foreach($topic->files as $file)
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-light rounded" id="file-{{ $file->id }}">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $file->original_name }}</div>
                                        <small class="text-muted">{{ $file->formatted_size }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removeFile({{ $file->id }}, 'topic')">
                                    <i class="bi bi-trash"></i> Удалить
                                </button>
                            </div>
                            <input type="hidden" name="remove_files[]" id="remove-file-{{ $file->id }}" value="" disabled>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Управление галереей -->
                    @if($topic->galleryImages && $topic->galleryImages->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-images me-1"></i>Галерея изображений
                        </label>
                        <div class="border rounded p-3">
                            <div class="row">
                                @foreach($topic->galleryImages as $image)
                                <div class="col-md-3 mb-3" id="gallery-{{ $image->id }}">
                                    <div class="card">
                                        <img src="{{ $image->thumbnail_url }}" class="card-img-top"
                                             style="height: 150px; object-fit: cover;" alt="{{ $image->original_name }}">
                                        <div class="card-body p-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                                    onclick="removeGalleryImage({{ $image->id }})">
                                                <i class="bi bi-trash"></i> Удалить
                                            </button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="remove_gallery[]" id="remove-gallery-{{ $image->id }}" value="" disabled>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('topics.show', $topic->id) }}" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/quill-forum.js') }}"></script>

<script>
// Удаление файла
function removeFile(fileId, type) {
    if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        // Скрываем элемент
        const fileElement = document.getElementById(`file-${fileId}`);
        if (fileElement) {
            fileElement.style.opacity = '0.5';
            fileElement.style.pointerEvents = 'none';
        }

        // Активируем скрытое поле для отправки ID удаленного файла
        const hiddenInput = document.getElementById(`remove-file-${fileId}`);
        if (hiddenInput) {
            hiddenInput.value = fileId;
            hiddenInput.disabled = false;
        }
    }
}

// Удаление изображения галереи
function removeGalleryImage(imageId) {
    if (confirm('Вы уверены, что хотите удалить это изображение из галереи?')) {
        // Скрываем элемент
        const galleryElement = document.getElementById(`gallery-${imageId}`);
        if (galleryElement) {
            galleryElement.style.opacity = '0.5';
            galleryElement.style.pointerEvents = 'none';
        }

        // Активируем скрытое поле для отправки ID удаленного изображения
        const hiddenInput = document.getElementById(`remove-gallery-${imageId}`);
        if (hiddenInput) {
            hiddenInput.value = imageId;
            hiddenInput.disabled = false;
        }
    }
}
</script>
@endsection