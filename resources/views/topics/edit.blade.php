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
@endsection