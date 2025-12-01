@extends('layouts.app')

@section('content')
<!-- Навигация -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">Форум</a></li>
        <li class="breadcrumb-item active">Создать тему</li>
    </ol>
</nav>

<div class="row">
    <!-- Основная форма -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Создать новую тему</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('topics.store') }}" enctype="multipart/form-data" id="topic-form">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Заголовок темы <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                                    name="title" value="{{ old('title') }}" placeholder="Введите заголовок темы..."
                                    required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Раздел <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id"
                                    name="category_id" required>
                                    <option value="">Выберите раздел...</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        data-allow-gallery="{{ $category->allow_gallery ? '1' : '0' }}"
                                        {{ (old('category_id') == $category->id || (isset($selectedCategoryId) && $selectedCategoryId == $category->id)) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Теги</label>
                                <div id="tags-container" class="border rounded p-3 bg-light"
                                    style="min-height: 80px; max-height: 150px; overflow-y: auto;">
                                    <p class="text-muted mb-0">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Сначала выберите категорию, чтобы увидеть доступные теги
                                    </p>
                                </div>
                                @error('tags')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">
                            Содержание темы <span class="text-danger">*</span>
                        </label>

                        <div class="editor-container">
                            <!-- Quill Editor -->
                            <div id="quill-editor" style="min-height: 200px;"></div>

                            <!-- Скрытое поле для отправки HTML -->
                            <textarea id="content" name="content" style="display: none;"
                                required>{{ old('content') }}</textarea>

                            <!-- Счетчик символов -->
                            <div class="char-counter" id="char-count">0 символов</div>

                            @error('content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Галерея изображений (показывается только для категорий с разрешенной галереей) -->
                    <div class="mb-3" id="gallerySection" style="display: none;">
                        <label class="form-label">
                            <i class="bi bi-images me-2"></i>Галерея изображений
                        </label>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Требования к изображениям:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Максимум 20 изображений</li>
                                <li>Размер каждого файла не более 10 МБ</li>
                                <li>Форматы: JPEG, PNG, GIF, WebP</li>
                                <li>Можно добавить описание к каждому изображению</li>
                            </ul>
                        </div>

                        <div class="gallery-upload-area border rounded p-4 text-center bg-light">
                            <input type="file" id="galleryInput" name="gallery[]" multiple
                                accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                            <button type="button" class="btn btn-outline-primary"
                                onclick="document.getElementById('galleryInput').click()">
                                <i class="bi bi-cloud-upload me-2"></i>Выбрать изображения
                            </button>
                            <p class="text-muted mt-2 mb-0">или перетащите файлы сюда</p>
                        </div>

                        <!-- Превью загруженных изображений -->
                        <div id="galleryPreview" class="row mt-3"></div>
                        <div id="gallery-hidden-fields"></div>

                        @error('gallery')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @error('gallery.*')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Загрузка файлов -->
                    <div class="mb-4">
                        <label for="files" class="form-label">
                            Прикрепить файлы <small class="text-muted">(необязательно, максимум 5 файлов по 10MB
                                каждый)</small>
                        </label>
                        <div class="border rounded p-3 bg-light">
                            <div class="files-upload-area text-center">
                                <input type="file" id="filesInput" multiple
                                    accept=".zip,.rar,.7z,.txt,.pdf,.doc,.docx,.json,.xml" class="d-none">
                                <button type="button" class="btn btn-outline-primary"
                                    onclick="document.getElementById('filesInput').click()">
                                    <i class="bi bi-cloud-upload me-2"></i>Выбрать файлы
                                </button>
                                <p class="text-muted mt-2 mb-0">или перетащите файлы сюда</p>
                            </div>

                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Разрешённые форматы: ZIP, RAR, 7Z, TXT, PDF, DOC, DOCX, JSON, XML
                                </small>
                            </div>

                            <!-- Превью загруженных файлов -->
                            <div id="filesPreview" class="mt-3"></div>
                            <div id="files-hidden-fields"></div>

                            @error('files')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            @error('files.*')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Предварительный просмотр -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-outline-info" id="preview-btn">
                            <i class="bi bi-eye me-1"></i>Предварительный просмотр
                        </button>

                        <div id="content-preview" class="mt-3 p-3 border rounded bg-light" style="display: none;">
                            <h6>Предварительный просмотр:</h6>
                            <div id="preview-content"></div>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('forum.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Назад
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bi bi-plus-circle me-1"></i>Создать тему
                        </button>
                    </div>
                </form>
                @if($errors->has('flood'))
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-clock me-2"></i>
                    {{ $errors->first('flood') }}
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Боковая панель с правилами и информацией -->
    <div class="col-lg-4">
        <!-- Правила форума -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Правила форума</h6>
            </div>
            <div class="card-body">
                <ol class="mb-0 small">
                    <li class="mb-2">Будьте вежливы и уважительны к другим участникам</li>
                    <li class="mb-2">Используйте информативные заголовки тем</li>
                    <li class="mb-2">Не создавайте дублирующие темы - используйте поиск</li>
                    <li class="mb-2">Размещайте темы в соответствующих разделах</li>
                    <li class="mb-2">Запрещен спам, флуд и оскорбления</li>
                    <li class="mb-2">Не размещайте личную информацию</li>
                    <li class="mb-0">Соблюдайте авторские права</li>
                </ol>
            </div>
        </div>

        <!-- Горячие клавиши -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-keyboard me-2"></i>Горячие клавиши</h6>
            </div>
            <div class="card-body">
                <div class="row small">
                    <div class="col-6">
                        <div class="mb-2"><kbd>Ctrl+B</kbd></div>
                        <div class="mb-2"><kbd>Ctrl+I</kbd></div>
                        <div class="mb-2"><kbd>Ctrl+K</kbd></div>
                        <div class="mb-2"><kbd>Ctrl+Enter</kbd></div>
                    </div>
                    <div class="col-6">
                        <div class="mb-2 text-muted">Жирный</div>
                        <div class="mb-2 text-muted">Курсив</div>
                        <div class="mb-2 text-muted">Ссылка</div>
                        <div class="mb-2 text-muted">Отправить</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Информация о файлах -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Загрузка файлов</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-3">
                        <strong>Ограничения:</strong>
                        <ul class="mb-2">
                            <li>Максимум 5 файлов</li>
                            <li>До 10 МБ каждый файл</li>
                            <li>Общий размер до 50 МБ</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <strong>Разрешенные форматы:</strong>
                        <div class="mt-2">
                            <span class="badge bg-secondary me-1 mb-1">ZIP</span>
                            <span class="badge bg-secondary me-1 mb-1">RAR</span>
                            <span class="badge bg-secondary me-1 mb-1">7Z</span>
                            <span class="badge bg-info me-1 mb-1">TXT</span>
                            <span class="badge bg-info me-1 mb-1">JSON</span>
                            <span class="badge bg-info me-1 mb-1">XML</span>
                            <span class="badge bg-success me-1 mb-1">PDF</span>
                            <span class="badge bg-success me-1 mb-1">DOC</span>
                            <span class="badge bg-success me-1 mb-1">DOCX</span>
                        </div>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Внимание:</strong> Исполняемые файлы (.exe, .bat, .sh) запрещены для загрузки по
                        соображениям безопасности.
                    </div>
                </div>
            </div>
        </div>

        <!-- Полезные советы -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Полезные советы</h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <ul class="mb-0">
                        <li class="mb-2">Используйте предварительный просмотр перед публикацией</li>
                        <li class="mb-2">Добавляйте теги для лучшего поиска</li>
                        <li class="mb-2">Структурируйте текст заголовками и списками</li>
                        <li class="mb-2">Прикрепляйте примеры кода как файлы</li>
                        <li class="mb-0">Отвечайте на комментарии для активного обсуждения</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const tagsContainer = document.getElementById('tags-container');
    const gallerySection = document.getElementById('gallerySection');
    const galleryInput = document.getElementById('galleryInput');
    const galleryPreview = document.getElementById('galleryPreview');

    let uploadedGalleryImages = [];
    let uploadedFiles = [];

    // При изменении категории
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;

            if (categoryId) {
                const selectedOption = this.options[this.selectedIndex];
                const allowGallery = selectedOption.getAttribute('data-allow-gallery') === '1';

                // Показываем/скрываем секцию галереи
                if (allowGallery && gallerySection) {
                    gallerySection.style.display = 'block';
                } else if (gallerySection) {
                    gallerySection.style.display = 'none';
                    clearGallery();
                }

                // Загружаем теги для категории
                loadTagsForCategory(categoryId);
            } else {
                if (gallerySection) {
                    gallerySection.style.display = 'none';
                    clearGallery();
                }
                if (tagsContainer) {
                    tagsContainer.innerHTML =
                        '<p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Сначала выберите категорию, чтобы увидеть доступные теги</p>';
                }
            }
        });

        // Проверяем при загрузке страницы
        if (categorySelect.value) {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const allowGallery = selectedOption.getAttribute('data-allow-gallery') === '1';

            if (allowGallery && gallerySection) {
                gallerySection.style.display = 'block';
            }

            loadTagsForCategory(categorySelect.value);
        }
    }

    // Обработка галереи
    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            handleGalleryFiles(Array.from(e.target.files));
            this.value = '';
        });
    }

    // Drag & Drop для галереи
    const uploadArea = document.querySelector('.gallery-upload-area');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-primary');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
            const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            if (files.length > 0) {
                handleGalleryFiles(files);
            }
        });
    }

    // Обработка загрузки изображений галереи с временным сохранением
    async function handleGalleryFiles(files) {
        const maxFiles = 20;
        const maxFileSize = 10 * 1024 * 1024; // 10MB
        
        if (uploadedGalleryImages.length + files.length > maxFiles) {
            alert(`Максимум ${maxFiles} изображений`);
            return;
        }

        for (let file of files) {
            if (file.size > maxFileSize) {
                alert(`Файл ${file.name} слишком большой. Максимум 10MB`);
                continue;
            }
            
            if (!file.type.startsWith('image/')) {
                alert(`${file.name} не является изображением`);
                continue;
            }
            
            await uploadGalleryImage(file);
        }
    }

    // Загрузка изображения галереи во временную папку
    async function uploadGalleryImage(file) {
    const progressId = 'progress-' + Date.now() + Math.random();
    
    // Создаем элемент прогресса
    const progressItem = document.createElement('div');
    progressItem.className = 'upload-item';
    progressItem.id = progressId;
    progressItem.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <div class="flex-grow-1">
                <div class="fw-bold">${file.name}</div>
                <small class="text-muted">Загружается...</small>
            </div>
        </div>
        <div class="upload-progress">
            <div class="progress">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </div>
    `;
    
    galleryPreview.appendChild(progressItem);
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    try {
        // Создаем XMLHttpRequest для отслеживания прогресса
        const xhr = new XMLHttpRequest();
        const progressBar = progressItem.querySelector('.progress-bar');
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        });
        
        const response = await new Promise((resolve, reject) => {
            xhr.onload = () => resolve(xhr);
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.open('POST', '/gallery/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });

        const data = JSON.parse(response.responseText);

        if (data.success) {
            // Убираем прогресс, показываем успех
            progressItem.className = 'upload-item upload-success';
            progressItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <span class="fw-bold">${file.name} загружен успешно</span>
                </div>
            `;
            
            // Через 1 секунду удаляем и добавляем превью
            setTimeout(() => {
                progressItem.remove();
                
                // Добавляем в массив загруженных изображений
                uploadedGalleryImages.push({
                    id: data.file.id,
                    filename: data.file.filename,
                    original_name: data.file.original_name,
                    file_size: data.file.file_size,
                    width: data.file.width,
                    height: data.file.height,
                    thumbnail_url: data.file.thumbnail_url,
                    original_url: data.file.original_url,
                    original_path: data.file.original_path,
                    thumbnail_path: data.file.thumbnail_path,
                    description: ''
                });

                const index = uploadedGalleryImages.length - 1;
                addImagePreview(uploadedGalleryImages[index], index);
                updateGalleryHiddenFields();
            }, 1000);
        } else {
            // Показываем ошибку
            progressItem.className = 'upload-item upload-error';
            progressItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    <span class="fw-bold">Ошибка: ${data.message}</span>
                </div>
            `;
            
            setTimeout(() => progressItem.remove(), 3000);
        }
    } catch (error) {
        console.error('Upload error:', error);
        progressItem.className = 'upload-item upload-error';
        progressItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                <span class="fw-bold">Ошибка загрузки ${file.name}</span>
            </div>
        `;
        
        setTimeout(() => progressItem.remove(), 3000);
    }
}


    // Функция добавления превью для загруженного изображения
    function addImagePreview(imageData, index) {
        if (!galleryPreview) return;

        const col = document.createElement('div');
        col.className = 'col-md-4 col-sm-6 mb-3';
        col.innerHTML = `
            <div class="card gallery-image-card" data-image-id="${imageData.id}">
                <img src="${imageData.thumbnail_url}" class="card-img-top" style="height: 200px; object-fit: cover;">
                <div class="card-body p-2">
                    <input type="text" class="form-control form-control-sm mb-2 gallery-description" 
                           placeholder="Описание (необязательно)"
                           maxlength="500"
                           oninput="updateImageDescription(${index}, this.value)">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${formatFileSize(imageData.file_size)}</small>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="removeUploadedImage(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        galleryPreview.appendChild(col);
    }

    // Функция обновления описания изображения
    window.updateImageDescription = function(index, description) {
        if (uploadedGalleryImages[index]) {
            uploadedGalleryImages[index].description = description;
            updateGalleryHiddenFields();
        }
    };

    // Функция обновления скрытых полей формы
    function updateGalleryHiddenFields() {
        let hiddenFieldsContainer = document.getElementById('gallery-hidden-fields');
        if (!hiddenFieldsContainer) {
            hiddenFieldsContainer = document.createElement('div');
            hiddenFieldsContainer.id = 'gallery-hidden-fields';
            document.getElementById('topic-form').appendChild(hiddenFieldsContainer);
        }

        hiddenFieldsContainer.innerHTML = '';

        uploadedGalleryImages.forEach((image, index) => {
            const fields = `
                <input type="hidden" name="gallery_images[${index}][filename]" value="${image.filename}">
                <input type="hidden" name="gallery_images[${index}][original_name]" value="${image.original_name}">
                <input type="hidden" name="gallery_images[${index}][file_size]" value="${image.file_size}">
                <input type="hidden" name="gallery_images[${index}][width]" value="${image.width}">
                <input type="hidden" name="gallery_images[${index}][height]" value="${image.height}">
                <input type="hidden" name="gallery_images[${index}][description]" value="${image.description || ''}">
                <input type="hidden" name="gallery_images[${index}][original_path]" value="${image.original_path}">
                <input type="hidden" name="gallery_images[${index}][thumbnail_path]" value="${image.thumbnail_path}">
            `;
            hiddenFieldsContainer.innerHTML += fields;
        });
    }

    // Функция удаления загруженного изображения
    window.removeUploadedImage = async function(index) {
        if (!confirm('Удалить это изображение?')) return;

        try {
            const imageData = uploadedGalleryImages[index];
            
            // Отправляем запрос на удаление временного файла
            const response = await fetch('/gallery/delete-image', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    image_id: imageData.id
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Удаляем из массива
                uploadedGalleryImages.splice(index, 1);
                
                // Удаляем превью из DOM
                const card = document.querySelector(`[data-image-id="${imageData.id}"]`).closest('.col-md-4');
                if (card) {
                    card.remove();
                }
                
                // Обновляем скрытые поля
                updateGalleryHiddenFields();
                
                // Обновляем индексы в оставшихся превью
                updateImageIndices();
            } else {
                alert('Ошибка при удалении изображения: ' + (data.message || 'Неизвестная ошибка'));
            }
            
        } catch (error) {
            console.error('Delete error:', error);
            alert('Ошибка при удалении изображения');
        }
    };

    // Обновление индексов после удаления
    function updateImageIndices() {
        const cards = galleryPreview.querySelectorAll('.gallery-image-card');
        cards.forEach((card, newIndex) => {
            const descInput = card.querySelector('.gallery-description');
            const deleteBtn = card.querySelector('.btn-outline-danger');
            
            if (descInput) {
                descInput.setAttribute('oninput', `updateImageDescription(${newIndex}, this.value)`);
            }
            if (deleteBtn) {
                deleteBtn.setAttribute('onclick', `removeUploadedImage(${newIndex})`);
            }
        });
    }

    // === ОБРАБОТКА ОБЫЧНЫХ ФАЙЛОВ ===
    const filesInputNew = document.getElementById('filesInput');
    const filesPreview = document.getElementById('filesPreview');
    const filesUploadArea = document.querySelector('.files-upload-area');

    // Обработка выбора файлов
    if (filesInputNew) {
        filesInputNew.addEventListener('change', function(e) {
            handleFiles(Array.from(e.target.files));
            this.value = '';
        });
    }

    // Drag & Drop для файлов
    if (filesUploadArea) {
        filesUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-primary');
        });

        filesUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
        });

        filesUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-primary');
            handleFiles(Array.from(e.dataTransfer.files));
        });
    }

    // Обработка загрузки обычных файлов с временным сохранением
    async function handleFiles(files) {
        const maxFiles = 10;
        const maxFileSize = 10 * 1024 * 1024; // 10MB
        
        if (uploadedFiles.length + files.length > maxFiles) {
            alert(`Максимум ${maxFiles} файлов`);
            return;
        }

        for (let file of files) {
            if (file.size > maxFileSize) {
                alert(`Файл ${file.name} слишком большой. Максимум 10MB`);
                continue;
            }
            
            await uploadFile(file);
        }
    }

    // Загрузка файла во временную папку
    async function uploadFile(file) {
    const progressId = 'file-progress-' + Date.now() + Math.random();
    
    // Создаем элемент прогресса
    const progressItem = document.createElement('div');
    progressItem.className = 'upload-item';
    progressItem.id = progressId;
    progressItem.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <div class="flex-grow-1">
                <div class="fw-bold">${file.name}</div>
                <small class="text-muted">Загружается... (${formatFileSize(file.size)})</small>
            </div>
        </div>
        <div class="upload-progress">
            <div class="progress">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </div>
    `;
    
    filesPreview.appendChild(progressItem);
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    try {
        // Создаем XMLHttpRequest для отслеживания прогресса
        const xhr = new XMLHttpRequest();
        const progressBar = progressItem.querySelector('.progress-bar');
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
            }
        });
        
        const response = await new Promise((resolve, reject) => {
            xhr.onload = () => resolve(xhr);
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.open('POST', '/files/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });

        const data = JSON.parse(response.responseText);

        if (data.success) {
            // Убираем прогресс, показываем успех
            progressItem.className = 'upload-item upload-success';
            progressItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle text-success me-2"></i>
                    <span class="fw-bold">${file.name} загружен успешно</span>
                </div>
            `;
            
            // Через 1 секунду заменяем на превью файла
            setTimeout(() => {
                progressItem.remove();
                
                uploadedFiles.push({
                    id: data.file.id,
                    filename: data.file.filename,
                    original_name: data.file.original_name,
                    file_size: data.file.file_size,
                    formatted_size: data.file.formatted_size,
                    mime_type: data.file.mime_type
                });

                addFilePreview(data.file);
            }, 1000);
        } else {
            // Показываем ошибку
            progressItem.className = 'upload-item upload-error';
            progressItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    <span class="fw-bold">Ошибка: ${data.message}</span>
                </div>
            `;
            
            setTimeout(() => progressItem.remove(), 3000);
        }
    } catch (error) {
        console.error('Upload error:', error);
        progressItem.className = 'upload-item upload-error';
        progressItem.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                <span class="fw-bold">Ошибка загрузки ${file.name}</span>
            </div>
        `;
        
        setTimeout(() => progressItem.remove(), 3000);
    }
}


    // Добавление превью файла
    function addFilePreview(fileData) {
        if (!filesPreview) return;

        const fileItem = document.createElement('div');
        fileItem.className = 'uploaded-file-item d-flex align-items-center justify-content-between p-2 border rounded mb-2';
        fileItem.setAttribute('data-file-id', fileData.id);
        
        fileItem.innerHTML = `
            <div class="file-info">
                <div class="file-name fw-bold">${fileData.original_name}</div>
                <small class="text-muted">${fileData.formatted_size}</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="removeUploadedFile(${fileData.id})">
                <i class="bi bi-trash"></i>
            </button>
        `;
        
        filesPreview.appendChild(fileItem);
    }

    // Удаление загруженного файла
    window.removeUploadedFile = async function(fileId) {
        if (!confirm('Удалить этот файл?')) return;

        try {
            const response = await fetch('/files/delete-temp', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    file_id: fileId
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Удаляем из массива
                uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);
                
                // Удаляем превью из DOM
                const fileItem = document.querySelector(`[data-file-id="${fileId}"]`);
                if (fileItem) {
                    fileItem.remove();
                }
            } else {
                alert('Ошибка при удалении файла: ' + (data.message || 'Неизвестная ошибка'));
            }
            
        } catch (error) {
            console.error('Delete error:', error);
            alert('Ошибка при удалении файла');
        }
    };

    // Очистка всех файлов
    function clearGallery() {
        uploadedGalleryImages = [];
        uploadedFiles = [];
        
        if (galleryPreview) {
            galleryPreview.innerHTML = '';
        }
        if (galleryInput) {
            galleryInput.value = '';
        }
        if (filesPreview) {
            filesPreview.innerHTML = '';
        }

        const hiddenFieldsContainer = document.getElementById('gallery-hidden-fields');
        if (hiddenFieldsContainer) {
            hiddenFieldsContainer.innerHTML = '';
        }
    }

    // Форматирование размера файла
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return size.toFixed(1) + ' ' + units[unitIndex];
    }
});

// Категории с тегами
const categoriesWithTags = @json($categories);

function loadTagsForCategory(categoryId) {
    const tagsContainer = document.getElementById('tags-container');
    if (!tagsContainer) return;

    if (!categoryId) {
        tagsContainer.innerHTML =
            '<p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Сначала выберите категорию, чтобы увидеть доступные теги</p>';
        return;
    }

    // Найдем категорию в данных
    const category = categoriesWithTags.find(c => c.id == categoryId);

    if (!category || !category.active_tags || category.active_tags.length === 0) {
        tagsContainer.innerHTML = '<p class="text-muted mb-0">Для этой категории нет доступных тегов</p>';
        return;
    }

    // Создаем чекбоксы для тегов
    let tagsHtml = '<div class="row">';
    category.active_tags.forEach(tag => {
        tagsHtml += `
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tags[]" value="${tag.id}" id="tag_${tag.id}">
                    <label class="form-check-label d-flex align-items-center" for="tag_${tag.id}">
                        <span class="badge me-2" style="background-color: ${tag.color}; color: white;">${tag.name}</span>
                        ${tag.description ? `<small class="text-muted">${tag.description}</small>` : ''}
                    </label>
                </div>
            </div>
        `;
    });
    tagsHtml += '</div>';

    tagsContainer.innerHTML = tagsHtml;
}

// Обработка отправки формы
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('topic-form');
    const submitBtn = document.getElementById('submit-btn');
    let isSubmitting = false;

    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Создание...';

            // Разблокируем через 10 секунд на случай ошибки
            setTimeout(() => {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Создать тему';
            }, 10000);
        });
    }
});

// Стили для файлов
const style = document.createElement('style');
style.textContent = `
    .uploaded-file-item {
        transition: all 0.2s ease;
    }

    .uploaded-file-item:hover {
        background-color: #e9ecef !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .gallery-image-card {
        transition: transform 0.2s ease;
    }

    .gallery-image-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .upload-progress {
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .progress {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        background-color: #e9ecef;
    }

    .progress-bar {
        background: linear-gradient(45deg, #007bff, #0056b3);
        transition: width 0.3s ease;
        border-radius: 4px;
    }

    .upload-item {
        padding: 12px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
    }

    .upload-success {
        border-color: #28a745;
        background-color: #f8fff9;
    }

    .upload-error {
        border-color: #dc3545;
        background-color: #fff5f5;
    }
`;
document.head.appendChild(style);

// ========== СИСТЕМА ЧЕРНОВИКОВ ДЛЯ СОЗДАНИЯ ТЕМЫ ==========

const TOPIC_DRAFT_KEY = 'forum_draft_new_topic';
const TOPIC_DRAFT_EXPIRY_DAYS = 30;

// Сохранение черновика темы
function saveTopicDraft() {
    const titleField = document.getElementById('title');
    const categorySelect = document.getElementById('category_id');

    if (!titleField) return;

    const title = titleField.value.trim();

    // Получаем содержимое напрямую из Quill Editor
    let content = '';
    if (window.mainQuill) {
        content = window.mainQuill.root.innerHTML;
    }

    // Проверяем что содержимое не пустое
    if (content.trim() === '<p><br></p>' || content.trim() === '') {
        content = '';
    }

    // Сохраняем только если есть хоть какое-то содержимое
    if (!title && !content) return;

    // Собираем выбранные теги
    const selectedTags = [];
    document.querySelectorAll('input[name="tags[]"]:checked').forEach(checkbox => {
        selectedTags.push(checkbox.value);
    });

    const draft = {
        title: title,
        content: content,
        category_id: categorySelect ? categorySelect.value : '',
        tags: selectedTags,
        timestamp: Date.now(),
        expiresAt: Date.now() + (TOPIC_DRAFT_EXPIRY_DAYS * 24 * 60 * 60 * 1000)
    };

    try {
        localStorage.setItem(TOPIC_DRAFT_KEY, JSON.stringify(draft));
    } catch (e) {
        console.error('Ошибка сохранения черновика темы:', e);
    }
}

// Загрузка черновика темы
function loadTopicDraft() {
    try {
        const draftData = localStorage.getItem(TOPIC_DRAFT_KEY);
        if (!draftData) return null;

        const draft = JSON.parse(draftData);

        // Проверяем срок действия
        if (Date.now() > draft.expiresAt) {
            localStorage.removeItem(TOPIC_DRAFT_KEY);
            return null;
        }

        return draft;
    } catch (e) {
        console.error('Ошибка загрузки черновика темы:', e);
        return null;
    }
}

// Очистка черновика темы
function clearTopicDraft() {
    try {
        localStorage.removeItem(TOPIC_DRAFT_KEY);
    } catch (e) {
        console.error('Ошибка очистки черновика темы:', e);
    }
}

// Восстановление черновика при загрузке страницы
function restoreTopicDraft() {
    const draft = loadTopicDraft();
    if (!draft) return;

    // Автоматически восстанавливаем без уведомления
    const titleField = document.getElementById('title');
    const contentField = document.getElementById('content');
    const categorySelect = document.getElementById('category_id');

    if (titleField) titleField.value = draft.title || '';
    if (contentField) contentField.value = draft.content || '';

    if (categorySelect && draft.category_id) {
        categorySelect.value = draft.category_id;
        // Триггерим событие изменения для загрузки тегов
        categorySelect.dispatchEvent(new Event('change'));

        // Восстанавливаем выбранные теги после небольшой задержки
        setTimeout(() => {
            if (draft.tags && draft.tags.length > 0) {
                draft.tags.forEach(tagId => {
                    const checkbox = document.getElementById('tag_' + tagId);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        }, 300);
    }

    // Восстанавливаем в Quill Editor
    // Ждём инициализации Quill
    const waitForQuillEditor = setInterval(() => {
        if (window.mainQuill && draft.content) {
            clearInterval(waitForQuillEditor);

            // Устанавливаем HTML контент напрямую
            const delta = window.mainQuill.clipboard.convert(draft.content);
            window.mainQuill.setContents(delta, 'silent');
        }
    }, 100);

    // Останавливаем ожидание через 5 секунд
    setTimeout(() => clearInterval(waitForQuillEditor), 5000);
}

// Автосохранение каждые 15 секунд
let topicDraftSaveTimer;
function startTopicDraftAutosave() {
    if (topicDraftSaveTimer) {
        clearInterval(topicDraftSaveTimer);
    }

    topicDraftSaveTimer = setInterval(() => {
        saveTopicDraft();
    }, 15000); // Каждые 15 секунд
}

// Инициализация системы черновиков для создания темы
document.addEventListener('DOMContentLoaded', function() {
    // Восстанавливаем черновик при загрузке
    setTimeout(() => {
        restoreTopicDraft();
    }, 1000);

    // Запускаем автосохранение
    startTopicDraftAutosave();

    // Сохраняем при изменении полей
    const titleField = document.getElementById('title');
    const categorySelect = document.getElementById('category_id');

    if (titleField) {
        titleField.addEventListener('input', function() {
            clearTimeout(window.topicDraftSaveTimeout);
            window.topicDraftSaveTimeout = setTimeout(saveTopicDraft, 2000);
        });
    }

    // Отслеживаем изменения в Quill Editor для содержимого
    const waitForQuillEditorInit = setInterval(() => {
        if (window.mainQuill) {
            clearInterval(waitForQuillEditorInit);

            // Отслеживаем изменения в Quill Editor
            window.mainQuill.on('text-change', function() {
                clearTimeout(window.topicDraftSaveTimeout);
                window.topicDraftSaveTimeout = setTimeout(saveTopicDraft, 2000);
            });
        }
    }, 100);

    // Останавливаем ожидание через 10 секунд
    setTimeout(() => clearInterval(waitForQuillEditorInit), 10000);

    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            saveTopicDraft();
        });
    }

    // Сохраняем при изменении тегов
    document.addEventListener('change', function(e) {
        if (e.target.name === 'tags[]') {
            saveTopicDraft();
        }
    });

    // Очищаем черновик при успешной отправке формы
    const topicForm = document.getElementById('topic-form');
    if (topicForm) {
        topicForm.addEventListener('submit', function() {
            // Очищаем через небольшую задержку, чтобы успела отправиться форма
            setTimeout(() => {
                clearTopicDraft();
            }, 1000);
        });
    }

    // Очистка всех устаревших черновиков
    cleanAllExpiredDrafts();
});

// Очистка всех устаревших черновиков
function cleanAllExpiredDrafts() {
    try {
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && (key.startsWith('forum_draft_'))) {
                const draftData = localStorage.getItem(key);
                if (draftData) {
                    try {
                        const draft = JSON.parse(draftData);
                        if (draft.expiresAt && Date.now() > draft.expiresAt) {
                            keysToRemove.push(key);
                        }
                    } catch (e) {
                        keysToRemove.push(key);
                    }
                }
            }
        }

        keysToRemove.forEach(key => localStorage.removeItem(key));

        if (keysToRemove.length > 0) {
            console.log(`Очищено устаревших черновиков: ${keysToRemove.length}`);
        }
    } catch (e) {
        console.error('Ошибка очистки устаревших черновиков:', e);
    }
}

// Функция showToast для уведомлений (если её нет в layouts/app.blade.php)
if (typeof showToast === 'undefined') {
    function showToast(type, title, message) {
        alert(title + ': ' + message);
    }
}
</script>

<script src="{{ asset('js/quill-forum.js') }}"></script>
@endsection
@endsection