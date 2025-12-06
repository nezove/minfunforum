@extends('layouts.app')

@section('title', $seoTitle)
@section('meta_description', $seoDescription)
@section('meta_keywords', $seoKeywords)

@section('content')
<div class="container">
    <!-- Заголовок тега -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <span class="badge me-2" style="background-color: {{ $tag->color }}; color: white;">
                    {{ $tag->name }}
                </span>
                в {{ $category->name }}
            </h2>
            @if($tag->description)
            <p class="text-muted mb-0">{{ $tag->description }}</p>
            @endif
        </div>

        @auth
        <a href="{{ route('topics.create', ['category' => $category->id]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Создать тему
        </a>
        @else
        <a href="{{ route('login') }}" class="btn btn-outline-primary">
            <i class="bi bi-box-arrow-in-right me-1"></i>Войти для создания темы
        </a>
        @endauth
    </div>

    <!-- Навигация -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">Форум</a></li>
            <li class="breadcrumb-item"><a href="{{ route('forum.category', $category->id) }}">{{ $category->name }}</a></li>
            <li class="breadcrumb-item active">Тег: {{ $tag->name }}</li>
        </ol>
    </nav>

    <!-- Список тем с тегом -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Темы с тегом "{{ $tag->name }}"</h5>
            <small class="text-muted">{{ $topics->total() }} тем</small>
        </div>
        <div class="card-body p-0">
            @forelse($topics as $topic)
            <div class="p-3 border-bottom hover-bg-light">
                <div class="d-flex align-items-start">
                    <!-- Иконки статуса темы -->
                    <div class="me-3 mt-1 d-flex flex-column align-items-center">
                        @if($topic->shouldShowPinIconOnCategoryPage())
                            @if($topic->isPinnedGlobally())
                                <i class="bi bi-pin-fill text-danger mb-1" title="Закреплено глобально"></i>
                            @else
                                <i class="bi bi-pin-angle-fill text-warning mb-1" title="Закреплено в категории"></i>
                            @endif
                        @endif

                        @if($topic->is_closed)
                            <i class="bi bi-lock-fill text-danger" title="Тема закрыта"></i>
                        @else
                            <i class="bi bi-chat-dots text-muted" title="Активная тема"></i>
                        @endif
                    </div>

                    <!-- Содержимое темы -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-1">
                                <a href="{{ route('topics.show', $topic) }}" class="text-decoration-none">
                                    {{ $topic->title }}
                                </a>
                            </h6>
                        </div>

                        <!-- Теги темы -->
                        @if($topic->tags->count() > 0)
                            <div class="mb-2">
                                @foreach($topic->tags as $topicTag)
                                    <a href="{{ route('tags.show', ['category' => $category->id, 'tag' => $topicTag->slug]) }}" 
                                       class="badge text-decoration-none me-1" 
                                       style="background-color: {{ $topicTag->color }}; color: white;">
                                        {{ $topicTag->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Информация о теме -->
                        <div class="d-flex align-items-center text-muted small">
                            <span class="me-3">
                                <i class="bi bi-person me-1"></i>
                                {{ config('app.display_username_instead_of_name', false) ? $topic->user->username : $topic->user->name }}
                            </span>
                            <span class="me-3">{{ $topic->created_at->diffForHumans() }}</span>
                            
                            @if($topic->lastPost && $topic->lastPost->user_id !== $topic->user_id)
                                <span class="me-3">
                                    <i class="bi bi-arrow-return-right me-1"></i>
                                    {{ config('app.display_username_instead_of_name', false) ? $topic->lastPost->user->username : $topic->lastPost->user->name }}
                                    {{ $topic->lastPost->created_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Статистика -->
                    <div class="text-end">
                        <div class="d-flex flex-column align-items-end gap-1">
                            <span class="d-flex align-items-center text-muted">
                                <i class="bi bi-chat me-1"></i>
                                <span class="fw-medium text-success">{{ $topic->replies_count }}</span>
                                <span class="ms-1">{{ $topic->replies_count == 1 ? 'ответ' : 'ответов' }}</span>
                            </span>
                            <span class="d-flex align-items-center text-muted">
                                <i class="bi bi-eye me-1"></i>
                                <span class="fw-medium text-info">{{ $topic->views }}</span>
                                <span class="ms-1">{{ $topic->views == 1 ? 'просмотр' : 'просмотров' }}</span>
                            </span>
                            @if($topic->likes_count > 0)
                                <span class="d-flex align-items-center text-muted">
                                    <i class="bi bi-heart-fill text-danger me-1"></i>
                                    <span class="fw-medium">{{ $topic->likes_count }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Быстрые действия для модераторов -->
                    @if(auth()->check() && auth()->user()->canModerate())
                        <div class="dropdown ms-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form action="{{ route('moderation.topic.toggle-status', $topic) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                            {{ $topic->is_closed ? 'Открыть' : 'Закрыть' }}
                                        </button>
                                    </form>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="showMoveTopicModal({{ $topic->id }}, '{{ $topic->title }}')">
                                        <i class="bi bi-arrow-right me-2"></i>Переместить
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('moderation.topic.delete', $topic) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить тему?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Удалить
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-4 text-center text-muted">
                <i class="bi bi-chat-dots-fill fs-1 mb-3 d-block"></i>
                <h5>Нет тем с этим тегом</h5>
                <p>Будьте первым, кто создаст тему с тегом "{{ $tag->name }}"!</p>
                @auth
                <a href="{{ route('topics.create', ['category' => $category->id]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Создать тему
                </a>
                @endauth
            </div>
            @endforelse
        </div>
    </div>

    <!-- Пагинация -->
    @if($topics->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $topics->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection