@extends('layouts.app')

@section('title', 'Профиль ' . $user->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <!-- Основная информация о пользователе -->
            <div class="card">
                <div class="card-body text-center">
                    <!-- Аватар с индикатором статуса -->
                    <div class="position-relative mb-3">
                        <img src="{{ $user->avatar_url }}" alt="Аватар {{ $user->name }}"
                            class="rounded-circle {{ App\Helpers\UserDisplayHelper::getBannedUserClass($user) }}"
                            width="120" height="120" style="object-fit: cover;">

                        @if($user->isBanned())
                        <div class="position-absolute top-0 end-0">
                            @if($user->getBanType() === 'permanent')
                            <span class="badge bg-danger rounded-pill">
                                <i class="bi bi-person-x"></i>
                            </span>
                            @else
                            <span class="badge bg-warning text-dark rounded-pill">
                                <i class="bi bi-person-dash"></i>
                            </span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Никнейм пользователя -->
                    <h4 class="mb-1">
                        {!! $user->styled_username !!}
                    </h4>
@if(auth()->check() && auth()->id() !== $user->id)
    <div class="ms-auto">
        <a href="{{ route('messages.show', $user->id) }}" 
           class="btn btn-primary">
            <i class="bi bi-chat-dots-fill me-2"></i>Написать сообщение
        </a>
    </div>
@endif

                    <!-- Статус онлайн/офлайн -->
                    <div class="mb-2">
                        @if($user->last_activity_at && $user->last_activity_at >= now()->subMinutes(5))
                        <span class="badge bg-success">
                            <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>Онлайн
                        </span>
                        @elseif($user->last_activity_at && $user->last_activity_at >= now()->subMinutes(15))
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>Недавно в сети
                        </span>
                        @else
                        <span class="badge bg-secondary">
                            <i class="bi bi-circle me-1" style="font-size: 8px;"></i>Не в сети
                        </span>
                        @endif
                    </div>

                    <!-- Бейдж роли или статуса блокировки -->
                    <div class="mb-3">
                        {!! App\Helpers\UserDisplayHelper::getUserStatusBadge($user) !!}
                    </div>

                    <!-- Предупреждение о блокировке -->
                    @if($user->isBanned())
                    <div class="alert alert-{{ $user->getBanType() === 'permanent' ? 'danger' : 'warning' }} mb-3">
                        <div class="d-flex align-items-start">
                            <i
                                class="bi bi-{{ $user->getBanType() === 'permanent' ? 'person-x' : 'person-dash' }} me-2"></i>
                            <div class="text-start">
                                <small class="fw-bold">
                                    {{ $user->getBanType() === 'permanent' ? 'Пользователь заблокирован навсегда' : 'Пользователь временно ограничен' }}
                                </small>
                                <br>
                                <small>
                                    {!! App\Helpers\UserDisplayHelper::getBanStatusText($user) !!}
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Краткая статистика -->
                    <div class="d-flex justify-content-around text-center border-top pt-3">
                        <div>
                            <div class="fw-bold text-primary">{{ $user->topics->count() }}</div>
                            <small class="text-muted">Тем</small>
                        </div>
                        <div>
                            <div class="fw-bold text-success">{{ $user->posts->count() }}</div>
                            <small class="text-muted">Постов</small>
                        </div>
                        <div>
                            <div class="fw-bold text-warning">{{ $user->received_likes_count ?? 0 }}</div>
                            <small class="text-muted">Лайков</small>
                        </div>
                    </div>
                    <!-- Информация о времени -->
                    <div class="mt-3 pt-3 small text-muted">
                        <!-- Информация о последней активности -->
                        <div class="border-top pt-3">
                            <i class="bi bi-clock me-1"></i>
                            Последняя активность:
                            <br>
                            @if($user->last_activity_at)
                            @if($user->last_activity_at >= now()->subMinutes(5))
                            <small class="text-success fw-bold">Сейчас в сети</small>
                            @elseif($user->last_activity_at >= now()->subMinutes(15))
                            <small class="text-warning">{{ $user->last_activity_at->diffForHumans() }}</small>
                            @elseif($user->last_activity_at >= now()->subDay())
                            <small class="text-muted">{{ $user->last_activity_at->format('H:i') }}</small>
                            @elseif($user->last_activity_at >= now()->subWeek())
                            <small class="text-muted">{{ $user->last_activity_at->format('d.m в H:i') }}</small>
                            @else
                            <small class="text-muted">{{ $user->last_activity_at->format('d.m.Y') }}</small>
                            @endif
                            @else
                            <small class="text-muted">Неизвестно</small>
                            @endif
                        </div>

                        <div class="mt-3 pt-3 border-top text-muted">
                            <i class="bi bi-calendar-plus me-1"></i>
                            Зарегистрирован: {{ $user->created_at->format('d.m.Y') }}
                            <br>
                            <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                        </div>

                    </div>

                    <!-- Дополнительная информация для заблокированных -->
                    @if($user->isBanned() && auth()->check() && auth()->user()->canModerate())
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="text-warning">
                            <i class="bi bi-gear me-1"></i>Информация для модераторов
                        </h6>
                        <div class="text-start small">
                            <div><strong>Заблокирован:</strong> {{ $user->banned_at->format('d.m.Y H:i') }}</div>
                            @if($user->banned_until)
                            <div><strong>До:</strong> {{ $user->banned_until->format('d.m.Y H:i') }}</div>
                            @endif
                            @if($user->bannedByUser)
                            <div><strong>Модератор:</strong> {{ $user->bannedByUser->name }}</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Дополнительная информация -->
            @if($user->bio || $user->location || $user->website || $user->telegram)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">О пользователе</h6>
                </div>
                <div class="card-body">
                    @if($user->name)
                    <div class="mb-2">
                        <small class="text-muted">Имя:</small><br>
                        {{ $user->name }}
                    </div>
                    @endif

                    @if($user->location)
                    <div class="mb-2">
                        <i class="bi bi-geo-alt text-muted me-2"></i>{{ $user->location }}
                    </div>
                    @endif

                    @if($user->website)
                    <div class="mb-2">
                        <i class="bi bi-link-45deg text-muted me-2"></i>
                        <a href="{{ $user->website }}" target="_blank" rel="noopener">
                            {{ $user->website }}
                        </a>
                    </div>
                    @endif

                    @if($user->telegram)
                    <div class="mb-2">
                        <i class="bi bi-telegram text-primary me-2"></i>
                        <a href="https://t.me/{{ $user->telegram }}" target="_blank" rel="noopener" class="text-decoration-none">
                            {{ '@' . $user->telegram }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <x-footer />
        </div>

        <div class="col-lg-9">
            @if($user->bio)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Описание</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">{!! nl2br(preg_replace(
                        '/(https?:\/\/[^\s]+)/i',
                        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-decoration-none">$1</a>',
                        e($user->bio)
                        )) !!}</p>
                </div>
            </div>
            @endif

            <!-- Награды пользователя -->
            @if($user->achievements()->where('is_active', true)->count() > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="bi bi-trophy text-warning me-2"></i>Награды ({{ $user->achievements()->where('is_active', true)->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($user->achievements()->where('is_active', true)->orderBy('user_achievements.awarded_at', 'desc')->get() as $achievement)
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100 shadow-sm achievement-card" style="cursor: pointer;"
                                data-bs-toggle="modal"
                                data-bs-target="#achievementModal"
                                data-achievement-name="{{ $achievement->name }}"
                                data-achievement-description="{{ $achievement->description }}"
                                data-achievement-icon="{{ $achievement->icon ? asset('storage/' . $achievement->icon) : asset('images/achievements/default.png') }}"
                                data-achievement-date="{{ $achievement->pivot->awarded_at }}">
                                <div class="card-body text-center p-3">
                                    <img src="{{ $achievement->icon ? asset('storage/' . $achievement->icon) : asset('images/achievements/default.png') }}"
                                        alt="{{ $achievement->name }}"
                                        class="mb-2"
                                        style="max-width: 64px; max-height: 64px;">
                                    <h6 class="mb-1">{{ $achievement->name }}</h6>
                                    <small class="text-muted d-block">{{ \Carbon\Carbon::parse($achievement->pivot->awarded_at)->format('d.m.Y') }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Навигационные табы -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#wall-tab" type="button">
                        <i class="bi bi-journal-text me-1"></i>Стена ({{ $wallPosts->total() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#topics-tab" type="button">
                        <i class="bi bi-chat-square-text me-1"></i>Темы ({{ $user->topics->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#posts-tab" type="button">
                        <i class="bi bi-chat me-1"></i>Сообщения ({{ $user->posts->count() }})
                    </button>
                </li>
                @if($likedPosts->count() > 0 || $likedTopics->count() > 0)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#likes-tab" type="button">
                        <i class="bi bi-heart me-1"></i>Понравившееся
                    </button>
                </li>
                @endif
            </ul>

            <!-- Содержимое табов -->
            <div class="tab-content">
                <!-- Стена -->
                <div class="tab-pane fade show active" id="wall-tab">
                    @include('profile.partials.wall', ['user' => $user, 'wallPosts' => $wallPosts])
                </div>

                <!-- Темы пользователя -->
                <div class="tab-pane fade" id="topics-tab">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Темы пользователя</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($user->topics as $topic)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('topics.show', $topic->id) }}"
                                                class="text-decoration-none">
                                                @if($topic->is_pinned ?? false)
                                                <i class="bi bi-pin text-warning"></i>
                                                @endif
                                                @if($topic->is_closed ?? false)
                                                <i class="bi bi-lock text-muted"></i>
                                                @endif
                                                {{ $topic->title }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            {!! Str::limit(strip_tags($topic->content), 150) !!}
                                        </p>
                                        <small class="text-muted">
                                            в <a href="{{ route('forum.category', $topic->category->id ?? '#') }}"
                                                class="text-decoration-none">
                                                {{ $topic->category->name ?? 'Общее обсуждение' }}
                                            </a>
                                            • {{ $topic->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="flex-shrink-0 ms-3 text-end">
                                        <div class="text-muted small">
                                            <div><i class="bi bi-eye me-1"></i>{{ $topic->views ?? 0 }}</div>
                                            <div><i class="bi bi-chat me-1"></i>{{ $topic->replies_count ?? 0 }}</div>
                                            @if(($topic->likes_count ?? 0) > 0)
                                            <div><i
                                                    class="bi bi-heart-fill text-danger me-1"></i>{{ $topic->likes_count }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted py-5">
                                <i class="bi bi-chat-square-text display-4 mb-3"></i>
                                <p>Пользователь ещё не создавал тем</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Сообщения пользователя -->
                <div class="tab-pane fade" id="posts-tab">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Последние сообщения</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($user->posts as $post)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('topics.show', $post->topic->id ?? '#') }}"
                                                class="text-decoration-none">
                                                {{ $post->topic->title ?? 'Удалённая тема' }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            {!! Str::limit(strip_tags($post->content), 150) !!}
                                        </p>
                                        <small class="text-muted">
                                            в <a href="{{ route('forum.category', $post->topic->category->id ?? '#') }}"
                                                class="text-decoration-none">
                                                {{ $post->topic->category->name ?? 'Общее обсуждение' }}
                                            </a>
                                            • {{ $post->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        @if(($post->likes_count ?? 0) > 0)
                                        <span class="badge bg-light text-danger">
                                            <i class="bi bi-heart-fill"></i> {{ $post->likes_count }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted py-5">
                                <i class="bi bi-chat display-4 mb-3"></i>
                                <p>Пользователь ещё не оставлял сообщений</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Понравившееся -->
                @if($likedPosts->count() > 0 || $likedTopics->count() > 0)
                <div class="tab-pane fade" id="likes-tab">
                    <!-- Понравившиеся сообщения -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Понравившиеся сообщения</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            @forelse($likedPosts as $post)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <a href="{{ route('topics.show', $post->topic->id ?? '#') }}"
                                                class="text-decoration-none">
                                                {{ $post->topic->title ?? 'Удалённая тема' }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted">
                                            {!! Str::limit(strip_tags($post->content), 150) !!}
                                        </p>
                                        <small class="text-muted">
                                            автор: <strong>{{ $post->user->name ?? 'Удалённый пользователь' }}</strong>
                                            • в <a
                                                href="{{ route('forum.category', $post->topic->category->id ?? '#') }}"
                                                class="text-decoration-none">
                                                {{ $post->topic->category->name ?? 'Общее обсуждение' }}
                                            </a>
                                            • {{ $post->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="bi bi-heart-fill text-danger"></i>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="list-group-item text-center text-muted py-5">
                                <i class="bi bi-heart display-4 mb-3"></i>
                                <p>Пользователь ещё не ставил лайки сообщениям</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- CSS стили для заблокированных пользователей и статуса -->
<style>
.user-banned {
    filter: grayscale(50%) opacity(75%);
}

/* Анимация для индикатора активности */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
    }

    70% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}

.badge.bg-success i {
    animation: pulse 2s infinite;
}

/* Стили для разных статусов */
.activity-status-online {
    color: #28a745;
}

.activity-status-recent {
    color: #ffc107;
}

.activity-status-offline {
    color: #6c757d;
}

.achievement-card {
    transition: transform 0.2s;
}

.achievement-card:hover {
    transform: translateY(-5px);
}
</style>

<!-- Модальное окно награды -->
<div class="modal fade" id="achievementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="achievementModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="achievementModalIcon" src="" alt="" class="mb-3" style="max-width: 128px; max-height: 128px;">
                <p id="achievementModalDescription" class="text-muted"></p>
                <div class="border-top pt-3 mt-3">
                    <small class="text-muted">
                        <i class="bi bi-calendar-check me-1"></i>
                        Получена: <span id="achievementModalDate"></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const achievementModal = document.getElementById('achievementModal');
    if (achievementModal) {
        achievementModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const name = button.getAttribute('data-achievement-name');
            const description = button.getAttribute('data-achievement-description');
            const icon = button.getAttribute('data-achievement-icon');
            const date = button.getAttribute('data-achievement-date');

            document.getElementById('achievementModalLabel').textContent = name;
            document.getElementById('achievementModalDescription').textContent = description;
            document.getElementById('achievementModalIcon').src = icon;
            document.getElementById('achievementModalIcon').alt = name;

            const dateObj = new Date(date);
            const formattedDate = dateObj.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('achievementModalDate').textContent = formattedDate;
        });
    }
});
</script>
@endsection