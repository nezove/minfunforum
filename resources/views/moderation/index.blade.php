@extends('layouts.app')

@section('title', 'Панель модерации')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-shield-check text-warning me-2"></i>
                    Панель модерации
                </h1>
                <div>
                    <span class="badge bg-{{ auth()->user()->role_color }} fs-6">
                        {{ auth()->user()->role_name }}
                    </span>
                </div>
            </div>

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-primary">{{ $stats['total_users'] }}</h4>
                            <small class="text-muted">Пользователей</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-danger">{{ $stats['banned_users'] }}</h4>
                            <small class="text-muted">Заблокировано</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-info">{{ $stats['total_topics'] }}</h4>
                            <small class="text-muted">Тем</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-success">{{ $stats['total_posts'] }}</h4>
                            <small class="text-muted">Постов</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Быстрые действия</h6>
                            <div class="d-grid gap-2">
                                <a href="{{ route('moderation.users') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people me-1"></i>Управление пользователями
                                </a>
                                <a href="{{ route('moderation.categories') }}" class="btn btn-outline-success btn-sm">
    <i class="bi bi-folder me-1"></i>Управление категориями
</a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Последние темы -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-chat-dots me-2"></i>Последние темы
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($recentTopics as $topic)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="{{ route('topics.show', $topic) }}"
                                                    class="text-decoration-none">
                                                    {{ Str::limit($topic->title, 40) }}
                                                </a>
                                                @if($topic->is_closed)
                                                <span class="badge bg-secondary ms-1">Закрыта</span>
                                                @endif
                                            </h6>
                                            <p class="mb-1 small text-muted">
                                                <i class="bi bi-person me-1"></i>{{ $topic->user->name }}
                                                <span class="mx-2">•</span>
                                                <i class="bi bi-folder me-1"></i>{{ $topic->category->name }}
                                            </p>
                                            <small class="text-muted">{{ $topic->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form action="{{ route('moderation.topic.toggle-status', $topic) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i
                                                                class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                                            {{ $topic->is_closed ? 'Открыть' : 'Закрыть' }}
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-warning"
                                                        onclick="showMoveTopicModal({{ $topic->id }}, '{{ $topic->title }}')">
                                                        <i class="bi bi-arrow-right me-2"></i>Переместить
                                                    </button>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <form action="{{ route('moderation.topic.delete', $topic) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Удалить тему?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Удалить
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center p-3 text-muted">
                                    Нет недавних тем
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Последние посты -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-chat me-2"></i>Последние посты
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($recentPosts as $post)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="{{ route('topics.show', $post->topic) }}#post-{{ $post->id }}"
                                                    class="text-decoration-none">
                                                    {{ Str::limit($post->topic->title, 30) }}
                                                </a>
                                            </h6>
                                            <p class="mb-1 small">{{ Str::limit(strip_tags($post->content), 60) }}</p>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted">
                                                    <i class="bi bi-person me-1"></i>{{ $post->user->name }}
                                                    @if($post->user->role !== 'user')
                                                    <span
                                                        class="badge bg-{{ $post->user->role_color }} ms-1">{{ $post->user->role_name }}</span>
                                                    @endif
                                                </small>
                                                <small
                                                    class="text-muted ms-2">{{ $post->created_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('posts.edit', $post) }}">
                                                        <i class="bi bi-pencil me-2"></i>Редактировать
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <button class="dropdown-item text-danger"
                                                    onclick="showDeletePostModal({{ $post->id }})">
                                                    <i class="bi bi-trash me-2"></i>Удалить
                                                </button>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center p-3 text-muted">
                                    Нет недавних постов
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Заблокированные пользователи -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-person-x text-danger me-2"></i>Заблокированные
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                @forelse($bannedUsers as $user)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <a href="{{ route('profile.show', $user) }}"
                                                    class="text-decoration-none">
                                                    {{ $user->name }}
                                                </a>
                                                <span class="badge bg-danger ms-1">
                                                    {{ $user->getBanType() === 'permanent' ? 'Навсегда' : 'Временно' }}
                                                </span>
                                            </h6>
                                            @if($user->ban_reason)
                                            <p class="mb-1 small">{{ Str::limit($user->ban_reason, 50) }}</p>
                                            @endif
                                            <small class="text-muted">
                                                {{ $user->banned_at->diffForHumans() }}
                                                @if($user->bannedByUser)
                                                от {{ $user->bannedByUser->name }}
                                                @endif
                                            </small>
                                            @if($user->banned_until)
                                            <br>
                                            <small class="text-warning">
                                                До: {{ $user->banned_until->format('d.m.Y H:i') }}
                                            </small>
                                            @endif
                                        </div>
                                        <form action="{{ route('moderation.user.unban', $user) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Разблокировать пользователя?')">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center p-3 text-muted">
                                    Нет заблокированных пользователей
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно перемещения темы -->
<div class="modal fade" id="moveTopicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="moveTopicForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Переместить тему</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Переместить тему "<span id="topicTitle"></span>" в другую категорию:</p>
                    <select name="category_id" class="form-select" required>
                        <option value="">Выберите категорию</option>
                        @foreach(\App\Models\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Переместить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showMoveTopicModal(topicId, topicTitle) {
    document.getElementById('topicTitle').textContent = topicTitle;
    document.getElementById('moveTopicForm').action = `/moderation/topics/${topicId}/move`;
    new bootstrap.Modal(document.getElementById('moveTopicModal')).show();
}
</script>
@endsection