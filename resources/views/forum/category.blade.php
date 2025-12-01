@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Заголовок категории -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                {{ $category->name }}
            </h2>
            @if($category->description)
            <p class="text-muted mb-0">{{ $category->description }}</p>
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
            <li class="breadcrumb-item active">{{ $category->name }}</li>
        </ol>
    </nav>

    <!-- category.blade.php -->
    <!-- Теги категории -->
    @if($category->tagsWithTopics->count() > 0)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-3">
                <i class="bi bi-tags me-2"></i>Фильтр по тегам
            </h6>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($category->tagsWithTopics as $tag)
                @php
                $isSelected = $selectedTags->contains('id', $tag->id);
                $currentTags = request()->get('tags', []);
                if (is_string($currentTags)) {
                $currentTags = [$currentTags];
                }

                if ($isSelected) {
                // Убираем тег из фильтра
                $newTags = array_diff($currentTags, [$tag->slug]);
                } else {
                // Добавляем тег к фильтру
                $newTags = array_merge($currentTags, [$tag->slug]);
                }

                $filterUrl = route('forum.category', $category->id);
                if (!empty($newTags)) {
                $filterUrl .= '?' . http_build_query(['tags' => $newTags]);
                }
                @endphp

                <a href="{{ $filterUrl }}" class="badge text-decoration-none {{ $isSelected ? 'border border-2' : '' }}"
                    style="background-color: {{ $tag->color }}; color: white;">
                    {{ $tag->name }} ({{ $tag->topics_count }})
                    @if($isSelected)
                    <i class="bi bi-x ms-1"></i>
                    @endif
                </a>
                @endforeach
            </div>

            @if($selectedTags->count() > 0)
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted">Активные фильтры:</span>
                @foreach($selectedTags as $selectedTag)
                <span class="badge" style="background-color: {{ $selectedTag->color }}; color: white;">
                    {{ $selectedTag->name }}
                </span>
                @endforeach
                <a href="{{ route('forum.category', $category->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>Сбросить фильтры
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            Темы в категории
            @if($selectedTags->count() > 0)
            <small class="text-muted">(отфильтровано по тегам)</small>
            @endif
        </h5>
        <small class="text-muted">{{ $topics->total() }} тем</small>
    </div>
    <div class="card-body p-0">
        <div class="card-body p-0">
            @forelse($topics as $topic)
            <div class="p-2 p-md-3 border-bottom border-light">
                <div class="d-flex align-items-start gap-2">
                    <!-- Иконка статуса -->
                    <div class="flex-shrink-0">
                        @if($topic->is_closed)
                        <i class="bi bi-lock-fill text-danger"></i>
                        @elseif($topic->is_locked)
                        <i class="bi bi-lock text-warning"></i>
                        @elseif($topic->shouldShowPinIconOnCategoryPage())
                        @if($topic->isPinnedGlobally())
                        <i class="bi bi-pin-fill text-danger"></i>
                        @else
                        <i class="bi bi-pin-angle-fill text-warning"></i>
                        @endif
                        @else
                        <i class="bi bi-chat-dots text-muted"></i>
                        @endif
                    </div>

                    <!-- Основной контент -->
                    <div class="flex-grow-1" style="min-width: 0; overflow-wrap: break-word;">
                        <!-- Заголовок -->
                        <div class="d-flex align-items-start justify-content-between mb-1 gap-2">
                            <h6 class="mb-0 flex-grow-1" style="word-break: break-word; overflow-wrap: break-word;">
                                <a href="{{ route('topics.show', $topic) }}"
                                    class="text-decoration-none fw-medium text-dark lh-sm">
                                    {{ $topic->title }}
                                </a>
                            </h6>

                            <!-- Меню действий (только на десктопе) -->
                            @if(auth()->check() && (auth()->user()->canModerate() || auth()->id() ===
                            $topic->user_id))
                            <div class="dropdown d-none d-md-block flex-shrink-0">
                                <button class="btn btn-sm btn-outline-light text-muted border-0 p-1" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if(auth()->user()->canModerate())
                                    <li>
                                        <form action="{{ route('moderation.topic.toggle-status', $topic) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i
                                                    class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                                {{ $topic->is_closed ? 'Открыть' : 'Закрыть' }}
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <button class="dropdown-item"
                                            onclick="showPinModal({{ $topic->id }}, '{{ addslashes($topic->title) }}', '{{ addslashes($category->name) }}', '{{ $topic->pin_type }}')">
                                            <i class="bi bi-pin me-2"></i>Закрепление
                                        </button>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    @endif
                                    @if(auth()->id() === $topic->user_id)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('topics.edit', $topic) }}">
                                            <i class="bi bi-pencil me-2"></i>Редактировать
                                        </a>
                                    </li>
                                    @endif
                                    @if(auth()->user()->canModerate() || auth()->id() === $topic->user_id)
                                    <li>
                                        <form action="{{ route('moderation.topic.delete', $topic) }}" method="POST"
                                            onsubmit="return confirm('Удалить тему?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Удалить
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                            @endif
                        </div>

                        <!-- Теги -->
                        @if($topic->tags->count() > 0)
                        <div class="mb-2">
                            @foreach($topic->tags->take(2) as $topicTag)
                            <span class="badge me-1"
                                style="background-color: {{ $topicTag->color ?? '#6c757d' }}; font-size: 0.7rem;">
                                {{ $topicTag->name }}
                            </span>
                            @endforeach
                            @if($topic->tags->count() > 2)
                            <span class="badge bg-light text-muted"
                                style="font-size: 0.7rem;">+{{ $topic->tags->count() - 2 }}</span>
                            @endif
                        </div>
                        @endif

                        <!-- Нижняя панель: автор, время, статистика -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                            <!-- Левая часть: автор и время -->
                            <div class="d-flex align-items-center gap-2 text-muted flex-wrap">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $topic->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($topic->user->name) . '&size=16&background=6c757d&color=ffffff' }}"
                                        class="rounded-circle me-1" width="16" height="16" alt="">
                                    <a href="{{ route('profile.show', $topic->user) }}"
                                        class="text-decoration-none text-dark">
                                        {{ $topic->user->username }}
                                    </a>
                                </div>

                                <span>{{ $topic->created_at->diffForHumans() }}</span>

                                @if($topic->lastPost && $topic->lastPost->created_at != $topic->created_at)
                                <span class="d-none d-sm-flex align-items-center">
                                    <i class="bi bi-arrow-return-right text-primary me-1"></i>
                                    {{ $topic->lastPost->created_at->diffForHumans() }}
                                </span>
                                @endif
                            </div>

                            <!-- Правая часть: статистика -->
                            <div class="d-flex align-items-center gap-2 gap-md-3 flex-shrink-0">
                                <span class="d-flex align-items-center text-muted">
                                    <i class="bi bi-chat-dots me-1"></i>
                                    <span class="fw-medium text-primary">{{ $topic->replies_count }}</span>
                                </span>

                                <span class="d-flex align-items-center text-muted">
                                    <i class="bi bi-eye me-1"></i>
                                    <span class="fw-medium text-info">{{ number_format($topic->views) }}</span>
                                </span>

                                @if($topic->likes_count > 0)
                                <span class="d-flex align-items-center text-muted">
                                    <i class="bi bi-heart-fill text-danger me-1"></i>
                                    <span class="fw-medium">{{ $topic->likes_count }}</span>
                                </span>
                                @endif

                                <!-- Меню действий на мобильных -->
                                @if(auth()->check() && (auth()->user()->canModerate() || auth()->id() ===
                                $topic->user_id))
                                <div class="dropdown d-md-none">
                                    <button class="btn btn-sm btn-outline-light text-muted border-0 p-1"
                                        type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if(auth()->user()->canModerate())
                                        <li>
                                            <form action="{{ route('moderation.topic.toggle-status', $topic) }}"
                                                method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i
                                                        class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                                    {{ $topic->is_closed ? 'Открыть' : 'Закрыть' }}
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <button class="dropdown-item"
                                                onclick="showPinModal({{ $topic->id }}, '{{ addslashes($topic->title) }}', '{{ addslashes($category->name) }}', '{{ $topic->pin_type }}')">
                                                <i class="bi bi-pin me-2"></i>Закрепление
                                            </button>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        @endif
                                        @if(auth()->id() === $topic->user_id)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('topics.edit', $topic) }}">
                                                <i class="bi bi-pencil me-2"></i>Редактировать
                                            </a>
                                        </li>
                                        @endif
                                        @if(auth()->user()->canModerate() || auth()->id() === $topic->user_id)
                                        <li>
                                            <form action="{{ route('moderation.topic.delete', $topic) }}"
                                                method="POST" onsubmit="return confirm('Удалить тему?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Удалить
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-5 text-center text-muted">
                <i class="bi bi-chat-text mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5 class="mb-3">В этом разделе пока нет тем</h5>
                <p class="mb-4">Станьте первым, кто создаст тему в этой категории!</p>
                @auth
                @if(auth()->user()->canPerformActions())
                <a href="{{ route('topics.create', ['category' => $category->id]) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Создать первую тему
                </a>
                @else
                <div class="alert alert-warning d-inline-block">
                    <i class="bi bi-shield-x me-1"></i>Ваш аккаунт заблокирован.
                    <a href="{{ route('banned') }}" class="alert-link">Подробнее</a>
                </div>
                @endif
                @else
                <a href="{{ route('login') }}" class="btn btn-outline-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Войти для создания темы
                </a>
                @endauth
            </div>
            @endforelse
        </div>
    </div>

    @if(auth()->check() && auth()->user()->canModerate())
    <!-- Модальное окно для изменения закрепления -->
    <div class="modal fade" id="pinTopicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="pinTopicForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Управление закреплением темы</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Изменить закрепление темы "<span id="pinTopicTitle"></span>":</p>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="pin_type" id="pin_none" value="none">
                            <label class="form-check-label" for="pin_none">
                                <i class="bi bi-x-circle text-muted me-2"></i>
                                <strong>Не закреплять</strong>
                                <small class="d-block text-muted">Тема будет отображаться в обычном порядке</small>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="pin_type" id="pin_category"
                                value="category">
                            <label class="form-check-label" for="pin_category">
                                <i class="bi bi-pin-angle-fill text-warning me-2"></i>
                                <strong>Закрепить в категории</strong> "<span id="categoryName"></span>"
                                <small class="d-block text-muted">Тема будет вверху списка только в этой
                                    категории</small>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pin_type" id="pin_global"
                                value="global">
                            <label class="form-check-label" for="pin_global">
                                <i class="bi bi-pin-fill text-danger me-2"></i>
                                <strong>Закрепить глобально</strong>
                                <small class="d-block text-muted">Тема будет вверху на главной странице и во всех
                                    категориях</small>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Применить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showPinModal(topicId, topicTitle, categoryName, currentPinType) {
        document.getElementById('pinTopicTitle').textContent = topicTitle;
        document.getElementById('categoryName').textContent = categoryName;
        document.getElementById('pinTopicForm').action = `/moderation/topics/${topicId}/pin`;

        // Устанавливаем текущее значение
        document.getElementById('pin_' + currentPinType).checked = true;

        new bootstrap.Modal(document.getElementById('pinTopicModal')).show();
    }
    </script>
    @endif

    @if($topics->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $topics->links() }}
    </div>
    @endif
</div>
    @endsection