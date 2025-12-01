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
@endsection
@endsection