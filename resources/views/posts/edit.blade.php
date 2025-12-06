@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5>Редактировать ответ</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('posts.update', $post->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Содержание ответа</label>
                        <div class="editor-container">
                            <!-- Quill Editor -->
                            <div id="quill-editor" style="min-height: 200px;">{!! old('content', $post->content) !!}</div>

                            <!-- Скрытое поле для отправки HTML -->
                            <textarea id="content" name="content" style="display: none;" required>{{ old('content', $post->content) }}</textarea>

                            <!-- Счетчик символов -->
                            <div class="char-counter mt-2 text-muted" id="char-count">0 символов</div>
                        </div>

                        @error('content')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Управление прикрепленными файлами -->
                    @if($post->files && $post->files->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-paperclip me-1"></i>Прикрепленные файлы
                        </label>
                        <div class="border rounded p-3">
                            @foreach($post->files as $file)
                            <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-light rounded" id="post-file-{{ $file->id }}">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark text-primary me-2"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $file->original_name }}</div>
                                        <small class="text-muted">{{ $file->formatted_size }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="removePostFile({{ $file->id }})">
                                    <i class="bi bi-trash"></i> Удалить
                                </button>
                            </div>
                            <input type="hidden" name="remove_files[]" id="remove-post-file-{{ $file->id }}" value="" disabled>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('topics.show', $post->topic_id) }}" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="{{ asset('js/quill-forum.js') }}"></script>

<script>
// Удаление файла из поста
function removePostFile(fileId) {
    if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        // Скрываем элемент
        const fileElement = document.getElementById(`post-file-${fileId}`);
        if (fileElement) {
            fileElement.style.opacity = '0.5';
            fileElement.style.pointerEvents = 'none';
        }

        // Активируем скрытое поле для отправки ID удаленного файла
        const hiddenInput = document.getElementById(`remove-post-file-${fileId}`);
        if (hiddenInput) {
            hiddenInput.value = fileId;
            hiddenInput.disabled = false;
        }
    }
}
</script>
@endsection
@endsection