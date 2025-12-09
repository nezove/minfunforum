@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-9">
        <!-- Заголовок и кнопка создания темы -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 fw-bold">Последние темы</h1>
            @auth
            <a href="{{ route('topics.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Создать тему
            </a>
            @endauth
        </div>

        <!-- Категории -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-grid me-2"></i>Категории
                </h5>
            </div>
            <div class="card-body p-3">
                @forelse($categories as $category)
                <div class="list-group-item list-group-item-action border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <a href="{{ route('forum.category', $category->id) }}" class="text-decoration-none">
                                    {{ $category->name }}
                                </a>
                            </h6>
                            @if($category->description)
                            <small class="text-muted">{{ $category->description }}</small>
                            @endif
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Тем: {{ $category->topics_count }}</small>
                            <small class="text-muted">Сообщений: {{ $category->posts_count }}</small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Пока нет разделов форума</h5>
                </div>
                @endforelse
            </div>
        </div>

<!-- index.blade.php -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-clock-history me-2"></i>Последние темы
        </h5>
    </div>
    <div class="card-body p-0">
        @forelse($latestTopics as $topic)
        <div class="d-flex align-items-start p-3 {{ !$loop->last ? 'border-bottom' : '' }} hover-bg-light">
            <!-- Аватар пользователя -->
            <img src="{{ $topic->user->avatar_url ?? 'https://via.placeholder.com/40x40/6c757d/ffffff?text=' . substr($topic->user->name, 0, 1) }}"
                alt="{{ $topic->user->username }}" class="rounded-circle me-3 flex-shrink-0" width="40"
                height="40">

            <!-- Основная информация о теме -->
            <div class="flex-grow-1" style="min-width: 0; overflow-wrap: break-word;">
                <h6 class="mb-1" style="word-break: break-word;">
                    <a href="{{ route('topics.show', $topic->id) }}"
                        class="text-decoration-none d-inline-block"
                        style="max-width: 100%; word-wrap: break-word;">
                        @if($topic->shouldShowPinIconOnHomePage())
                        <i class="bi bi-pin-fill text-danger mb-1" title="Закреплено глобально"></i>
                        @endif

                        @if($topic->is_closed)
                        <i class="bi bi-lock text-danger me-1" title="Тема закрыта"></i>
                        @endif
                        @if($topic->is_locked)
                        <i class="bi bi-lock text-danger me-1" title="Тема заблокирована"></i>
                        @endif
                        {{ $topic->title }}
                    </a>
                </h6>
                <div class="small text-muted mb-2">
                    <a href="{{ route('profile.show', $topic->user->id) }}"
                        class="text-decoration-none">{{ $topic->user->username }}</a>
                    в
                    <a href="{{ route('forum.category', $topic->category->id) }}"
                        class="text-decoration-none">{{ $topic->category->name }}</a> •
                    {{ $topic->created_at->diffForHumans() }}
                </div>

                <!-- Мобильная версия счетчиков -->
                <div class="d-flex gap-3 small text-muted d-md-none">
                    <span><i class="bi bi-chat me-1"></i>{{ $topic->replies_count ?? 0 }}</span>
                    <span><i class="bi bi-eye me-1"></i>{{ $topic->views ?? 0 }}</span>
                    @if($topic->likes_count > 0)
                    <span><i class="bi bi-heart-fill text-danger me-1"></i>{{ $topic->likes_count }}</span>
                    @endif
                </div>
            </div>

            <!-- Десктопная версия счетчиков -->
            <div class="text-center me-3 d-none d-md-block flex-shrink-0">
                <div class="small text-muted">Ответы</div>
                <div class="fw-bold text-primary">{{ $topic->replies_count ?? 0 }}</div>
            </div>
            <div class="text-center me-3 d-none d-md-block flex-shrink-0">
                <div class="small text-muted">Просмотры</div>
                <div class="fw-bold text-info">{{ $topic->views ?? 0 }}</div>
            </div>
            @if($topic->likes_count > 0)
            <div class="text-center me-3 d-none d-md-block flex-shrink-0">
                <div class="small text-muted">Лайки</div>
                <div class="fw-bold text-danger">{{ $topic->likes_count }}</div>
            </div>
            @endif

            <!-- Информация о последнем сообщении -->
            <div class="text-end d-none d-lg-block flex-shrink-0" style="min-width: 120px;">
                @if($topic->lastPost && $topic->lastPost->created_at != $topic->created_at)
                <div class="small text-muted">{{ $topic->lastPost->created_at->diffForHumans() }}</div>
                <div class="small">
                    <a href="{{ route('profile.show', $topic->lastPost->user->id) }}"
                        class="text-decoration-none">{{ $topic->lastPost->user->name }}</a>
                </div>
                @elseif($topic->replies_count == 0)
                <div class="small text-muted">Нет ответов</div>
                @else
                <div class="small text-muted">{{ $topic->created_at->diffForHumans() }}</div>
                <div class="small">
                    <a href="{{ route('profile.show', $topic->user->id) }}"
                        class="text-decoration-none">{{ $topic->user->name }}</a>
                </div>
                @endif
            </div>

            <!-- Быстрые действия для модераторов -->
            @if(auth()->check() && auth()->user()->canModerate())
            <div class="dropdown d-none d-lg-block">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                    data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item"
                            onclick="showPinModalIndex({{ $topic->id }}, '{{ $topic->title }}', '{{ $topic->category->name }}', '{{ $topic->pin_type }}')">
                            <i class="bi bi-pin me-2"></i>Закрепление
                        </button>
                    </li>
                    <li>
                        <form action="{{ route('moderation.topic.toggle-status', $topic) }}" method="POST"
                            class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                {{ $topic->is_closed ? 'Открыть' : 'Закрыть' }}
                            </button>
                        </form>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <button class="dropdown-item text-danger"
                            onclick="showDeleteTopicModal({{ $topic->id }}, '{{ $topic->title }}')">
                            <i class="bi bi-trash me-2"></i>Удалить
                        </button>
                    </li>

                </ul>
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-5">
            <i class="bi bi-chat-text text-muted" style="font-size: 3rem;"></i>
            <h5 class="mt-3 text-muted">Пока нет тем</h5>
            <p class="text-muted">Станьте первым, кто создаст тему!</p>
            @auth
            @if(auth()->user()->canPerformActions())
            <a href="{{ route('topics.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Создать первую тему
            </a>
            @else
            <div class="alert alert-warning d-inline-block">
                <i class="bi bi-shield-x me-1"></i>Ваш аккаунт заблокирован
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

        <!-- Модальное окно для изменения закрепления на главной -->
        @if(auth()->check() && auth()->user()->canModerate())
        <div class="modal fade" id="pinTopicModalIndex" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="pinTopicFormIndex" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Управление закреплением темы</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Изменить закрепление темы "<span id="pinTopicTitleIndex"></span>":</p>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="pin_type" id="pin_none_index"
                                    value="none">
                                <label class="form-check-label" for="pin_none_index">
                                    <i class="bi bi-x-circle text-muted me-2"></i>
                                    <strong>Не закреплять</strong>
                                    <small class="d-block text-muted">Тема будет отображаться в обычном порядке</small>
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="pin_type" id="pin_category_index"
                                    value="category">
                                <label class="form-check-label" for="pin_category_index">
                                    <i class="bi bi-pin-angle-fill text-warning me-2"></i>
                                    <strong>Закрепить в категории</strong> "<span id="categoryNameIndex"></span>"
                                    <small class="d-block text-muted">Тема будет вверху списка только в этой
                                        категории</small>
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="pin_type" id="pin_global_index"
                                    value="global">
                                <label class="form-check-label" for="pin_global_index">
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
        @endif
    </div>

    @if(auth()->check() && auth()->user()->canModerate())
    <script>
    function showPinModalIndex(topicId, topicTitle, categoryName, currentPinType) {
        document.getElementById('pinTopicTitleIndex').textContent = topicTitle;
        document.getElementById('categoryNameIndex').textContent = categoryName;
        document.getElementById('pinTopicFormIndex').action = `/moderation/topics/${topicId}/pin`;

        // Устанавливаем текущее значение
        document.getElementById('pin_' + currentPinType + '_index').checked = true;

        new bootstrap.Modal(document.getElementById('pinTopicModalIndex')).show();
    }
    </script>
    @endif
    <!-- Боковая панель -->
    <div class="col-lg-3">
        <!-- Статистика -->
        <div class="card bg-primary text-white shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-3 fw-semibold">
                    <i class="bi bi-bar-chart me-2"></i>Статистика форума
                </h6>
                <div class="row">
                    <div class="col-6">
                        <div class="h4 mb-0">{{ \App\Models\Topic::count() }}</div>
                        <small class="opacity-75">Тем</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 mb-0">{{ \App\Models\Post::count() }}</div>
                        <small class="opacity-75">Ответов</small>
                    </div>
                </div>
                <hr class="my-3 border-light opacity-25">
                <div class="row">
                    <div class="col-6">
                        <div class="h4 mb-0">{{ \App\Models\User::count() }}</div>
                        <small class="opacity-75">Пользователей</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 mb-0 d-flex align-items-center">
                            {{ \App\Models\User::online()->count() }}
                            <span class="activity-indicator ms-2" style="width: 8px; height: 8px;"></span>
                        </div>
                        <small class="opacity-75">Онлайн сейчас</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Активные пользователи -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-people me-2"></i>Активные пользователи
                </h6>
            </div>
            <div class="card-body">
                @php
                // Получаем активных пользователей с правильной логикой
                $activeUsers = \App\Models\User::select('id', 'username', 'name', 'avatar', 'role', 'last_activity_at',
                'posts_count')
                ->where('last_activity_at', '>=', now()->subDays(30)) // За последние 30 дней
                ->whereHas('posts') // У кого есть хотя бы один пост
                ->get()
                ->map(function($user) {
                // Пересчитываем реальное количество постов
                $realPostsCount = $user->posts()->count();

                // Считаем лайки на посты пользователя за последние 30 дней
                $likesOnPosts = \DB::table('likes')
                ->join('posts', 'likes.likeable_id', '=', 'posts.id')
                ->where('likes.likeable_type', 'App\\Models\\Post')
                ->where('posts.user_id', $user->id)
                ->where('likes.created_at', '>=', now()->subDays(30))
                ->count();

                // Считаем лайки на темы пользователя за последние 30 дней
                $likesOnTopics = \DB::table('likes')
                ->join('topics', 'likes.likeable_id', '=', 'topics.id')
                ->where('likes.likeable_type', 'App\\Models\\Topic')
                ->where('topics.user_id', $user->id)
                ->where('likes.created_at', '>=', now()->subDays(30))
                ->count();

                $totalLikes = $likesOnPosts + $likesOnTopics;

                // Обновляем счётчик постов если неправильный
                if ($user->posts_count !== $realPostsCount) {
                $user->update(['posts_count' => $realPostsCount]);
                }

                $daysSinceActivity = $user->last_activity_at ?
                now()->diffInDays($user->last_activity_at) : 30;

                // Формула активности: лайки * 5 + посты * 1 + бонус за недавнюю активность
                $activityScore = ($totalLikes * 5) + ($realPostsCount * 1) + max(0, (30 - $daysSinceActivity));

                return [
                'user' => $user,
                'posts_count' => $realPostsCount,
                'likes_received' => $totalLikes,
                'recent_activity' => $user->last_activity_at,
                'activity_score' => $activityScore
                ];
                })
                ->sortByDesc('activity_score') // Сортируем по активности (приоритет лайкам)
                ->take(5); // Берём топ 5
                @endphp

                @forelse($activeUsers as $userData)
                @php $user = $userData['user']; @endphp
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ $user->avatar_url }}" class="rounded-circle me-3" width="40" height="40" alt="Avatar">
                    <div class="flex-grow-1">
                        <div class="fw-semibold mb-0">
                            <a href="{{ route('profile.show', $user->id) }}" class="text-decoration-none">
                                {{ $user->username }}
                            </a>
                            @if($user->role && $user->role !== 'user')
                            <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'warning' }} ms-1"
                                style="font-size: 0.65em;">
                                {{ $user->role === 'admin' ? 'Админ' : 'Модер' }}
                            </span>
                            @endif
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-chat-dots me-1"></i>{{ $userData['posts_count'] }}
                            {{ Str::plural('ответ', $userData['posts_count'], ['ответ', 'ответа', 'ответов']) }}
                            @if($userData['likes_received'] > 0)
                            <i class="bi bi-heart-fill text-danger ms-2 me-1"></i>{{ $userData['likes_received'] }}
                            @endif
                            <span class="text-muted ms-2" style="font-size: 0.8em;">
                                {{ $userData['recent_activity'] ? $userData['recent_activity']->diffForHumans() : 'давно' }}
                            </span>
                        </small>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted">
                    <i class="bi bi-people me-2"></i>Нет активных пользователей
                </div>
                @endforelse
            </div>
        </div>
        <!-- Кто сейчас онлайн -->
        @if(\App\Models\User::online()->count() > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-circle-fill text-success me-2" style="font-size: 0.7rem;"></i>
                    Сейчас онлайн ({{ \App\Models\User::online()->count() }})
                </h6>
            </div>
            <div class="card-body">
                @php
                $onlineUsers = \App\Models\User::online()
                ->orderBy('last_activity_at', 'desc')
                ->limit(10)
                ->get();
                @endphp
                <div class="d-flex flex-wrap gap-2">
                    @foreach($onlineUsers as $onlineUser)
                    <a href="{{ route('profile.show', $onlineUser->id) }}"
                        class="btn btn-sm btn-outline-success d-flex align-items-center text-decoration-none">
                        <img src="{{ $onlineUser->avatar_url }}" class="rounded-circle me-2" width="20" height="20"
                            alt="Avatar">
                        {{ $onlineUser->username }}
                        <span class="activity-indicator ms-1"></span>
                    </a>
                    @endforeach
                </div>
                @if(\App\Models\User::online()->count() > 10)
                <small class="text-muted mt-2 d-block">
                    И еще {{ \App\Models\User::online()->count() - 10 }} пользователей...
                </small>
                @endif
            </div>
        </div>
        @endif

        <x-footer class="mb-4" />
    </div>
</div>
@endsection