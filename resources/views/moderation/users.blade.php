@extends('layouts.app')

@section('title', 'Управление пользователями')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">
                    <i class="bi bi-people text-primary me-2"></i>
                    Управление пользователями
                </h1>
                <a href="{{ route('moderation.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Назад к панели
                </a>
            </div>

            <!-- Фильтры -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Поиск</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Имя, email или логин" 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Роль</label>
                            <select name="role" class="form-select">
                                <option value="">Все роли</option>
                                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Пользователь</option>
                                <option value="moderator" {{ request('role') === 'moderator' ? 'selected' : '' }}>Модератор</option>
                                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Администратор</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Статус</label>
                            <select name="status" class="form-select">
                                <option value="">Все статусы</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Активные</option>
                                <option value="banned" {{ request('status') === 'banned' ? 'selected' : '' }}>Заблокированные</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Найти
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список пользователей -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        Найдено пользователей: {{ $users->total() }}
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Пользователь</th>
                                <th>Роль</th>
                                <th>Статистика</th>
                                <th>Статус</th>
                                <th>Последняя активность</th>
                                <th width="200">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="{{ $user->isBanned() ? 'table-danger' : '' }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $user->avatar_url }}" 
                                                 class="rounded-circle me-3" 
                                                 width="40" height="40" 
                                                 alt="Avatar">
                                            <div>
                                                <div class="fw-semibold">
                                                    <a href="{{ route('profile.show', $user) }}" 
                                                       class="text-decoration-none">
                                                        {{ $user->name }}
                                                    </a>
                                                </div>
                                                <small class="text-muted">{{ $user->email }}</small>
                                                    @if(auth()->user()->isAdmin())
        <button type="button" 
                class="btn btn-outline-info btn-sm" 
                onclick="showUserActivity({{ $user->id }})" 
                title="Посмотреть активность пользователя">
            <i class="bi bi-activity"></i>
        </button>
    @endif

                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $user->role_color }}">
                                            {{ $user->role_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <div><i class="bi bi-chat-dots me-1"></i>{{ $user->topics_count }} тем</div>
                                            <div><i class="bi bi-chat me-1"></i>{{ $user->posts_count }} постов</div>
                                            <div><i class="bi bi-star me-1"></i>{{ $user->rating }} рейтинг</div>
                                        </small>
                                    </td>
                                    <td>
                                        @if($user->isBanned())
                                            <div>
                                                <span class="badge bg-danger">Заблокирован</span>
                                                @if($user->getBanType() === 'temporary')
                                                    <br><small class="text-muted">До: {{ $user->banned_until->format('d.m.Y H:i') }}</small>
                                                @else
                                                    <br><small class="text-muted">Навсегда</small>
                                                @endif
                                                @if($user->ban_reason)
                                                    <br><small class="text-muted" title="{{ $user->ban_reason }}">
                                                        {{ Str::limit($user->ban_reason, 30) }}
                                                    </small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge bg-success">Активен</span>
                                            @if($user->last_activity_at && $user->last_activity_at->diffInMinutes() < 5)
                                                <br><small class="text-success"><i class="bi bi-circle-fill"></i> Онлайн</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->last_activity_at)
                                            <small class="text-muted">{{ $user->last_activity_at->diffForHumans() }}</small>
                                        @else
                                            <small class="text-muted">Никогда</small>
                                        @endif
                                        <br>
                                        <small class="text-muted">Рег: {{ $user->created_at->format('d.m.Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical" role="group">
                                            @if(!$user->isBanned())
                                                <!-- Кнопка блокировки -->
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="showBanModal({{ $user->id }}, '{{ $user->name }}')"
                                                        {{ $user->isStaff() || $user->id === auth()->id() ? 'disabled' : '' }}>
                                                    <i class="bi bi-person-x me-1"></i>Заблокировать
                                                </button>
                                            @else
                                                <!-- Кнопка разблокировки -->
                                                <form action="{{ route('moderation.user.unban', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success"
                                                            onclick="return confirm('Разблокировать пользователя {{ $user->name }}?')">
                                                        <i class="bi bi-unlock me-1"></i>Разблокировать
                                                    </button>
                                                </form>
                                            @endif

                                            @if(auth()->user()->isAdmin() && $user->id !== auth()->id())
                                                <!-- Изменение роли (только для админов) -->
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-gear me-1"></i>Роль
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @foreach(['user' => 'Пользователь', 'moderator' => 'Модератор', 'admin' => 'Администратор'] as $role => $roleName)
                                                            @if($user->role !== $role)
                                                                <li>
                                                                    <form action="{{ route('moderation.user.role', $user) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        <input type="hidden" name="role" value="{{ $role }}">
                                                                        <button type="submit" class="dropdown-item"
                                                                                onclick="return confirm('Изменить роль на {{ $roleName }}?')">
                                                                            {{ $roleName }}
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-search mb-2" style="font-size: 2rem;"></i>
                                        <br>Пользователи не найдены
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($users->hasPages())
                    <div class="card-footer">
                        {{ $users->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@if(auth()->user()->isAdmin())
<div class="modal fade" id="userActivityModal" tabindex="-1" aria-labelledby="userActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userActivityModalLabel">
                    <i class="bi bi-activity me-2"></i>
                    Активность пользователя
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userActivityContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                    <p class="mt-2">Загрузка данных активности...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@endif
@if(auth()->user()->isAdmin())
<script>
function showUserActivity(userId) {
    // Показываем модальное окно
    const modal = new bootstrap.Modal(document.getElementById('userActivityModal'));
    modal.show();
    
    // Сбрасываем содержимое на загрузку
    document.getElementById('userActivityContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">Загрузка данных активности...</p>
        </div>
    `;
    
    // Загружаем данные активности
    fetch(`/moderation/users/${userId}/activity`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
    // ОТЛАДКА: выводим полученные данные в консоль
    //console.log('Полученные данные:', data);
    //console.log('Данные пользователя:', data.user);
    
    if (data.error) {
        throw new Error(data.message || 'Ошибка загрузки данных');
    }
    
    // Проверяем структуру данных перед использованием
    if (!data.user) {
        throw new Error('Данные пользователя отсутствуют в ответе сервера');
    }
    
    // Отображаем данные активности
    document.getElementById('userActivityContent').innerHTML = generateActivityHTML(data);

        
        // Отображаем данные активности
        document.getElementById('userActivityContent').innerHTML = generateActivityHTML(data);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('userActivityContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Ошибка при загрузке данных активности: ${error.message}
            </div>
        `;
    });
}

function generateActivityHTML(data) {
    const user = data.user;
    const stats = data.stats;
    const sessions = data.sessions;
    const suspicious = data.suspicious_activity;
    
    return `
        <!-- Информация о пользователе -->
        <div class="row mb-4">
            <div class="col-md-3 text-center">
                <img src="${user.avatar_url || '/storage/avatars/default-avatar.jpg'}" 
                     class="rounded-circle mb-2" width="80" height="80" alt="Avatar">
                <h6>${user.name}</h6>
                <p class="text-muted mb-1">@${user.username}</p>
                <span class="badge bg-${user.role === 'admin' ? 'danger' : user.role === 'moderator' ? 'warning' : 'secondary'}">${user.role}</span>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Email:</strong><br>
                        <span class="text-muted">${user.email}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Регистрация:</strong><br>
                        <span class="text-muted">${new Date(stats.registration_date).toLocaleString('ru-RU')}</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Последняя активность:</strong><br>
                        <span class="text-muted">${stats.last_activity ? new Date(stats.last_activity).toLocaleString('ru-RU') : 'Неизвестно'}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- НОВОЕ: Кнопки массового удаления (ТОЛЬКО ДЛЯ АДМИНОВ) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Опасные действия</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-danger btn-sm w-100" 
                                        onclick="confirmMassDelete('topics', '${user.username}', ${user.id}, ${stats.total_topics})"
                                        ${stats.total_topics === 0 ? 'disabled' : ''}>
                                    <i class="bi bi-trash me-1"></i>
                                    Удалить все темы (${stats.total_topics})
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-warning btn-sm w-100"
                                        onclick="confirmMassDelete('posts', '${user.username}', ${user.id}, ${stats.total_posts})"
                                        ${stats.total_posts === 0 ? 'disabled' : ''}>
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    Удалить все посты (${stats.total_posts})
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Действия необратимы! Требуется подтверждение имени пользователя.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Предупреждения о подозрительной активности -->
        ${suspicious.multiple_ips || suspicious.multiple_user_agents || suspicious.rapid_logins ? `
            <div class="alert alert-warning">
                <h6><i class="bi bi-exclamation-triangle me-2"></i>Подозрительная активность:</h6>
                <ul class="mb-0">
                    ${suspicious.multiple_ips ? '<li>Множественные IP адреса за 24 часа</li>' : ''}
                    ${suspicious.multiple_user_agents ? '<li>Множественные User-Agent за 24 часа</li>' : ''}
                    ${suspicious.rapid_logins ? '<li>Слишком частые входы в систему</li>' : ''}
                </ul>
            </div>
        ` : ''}
        
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="text-primary mb-1">${stats.total_topics}</h5>
                        <small class="text-muted">Тем</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="text-success mb-1">${stats.total_posts}</h5>
                        <small class="text-muted">Постов</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="text-info mb-1">${stats.total_likes_given}</h5>
                        <small class="text-muted">Лайков дал</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="text-warning mb-1">${stats.total_likes_received}</h5>
                        <small class="text-muted">Лайков получил</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="text-dark mb-1">${stats.account_age_days}</h5>
                        <small class="text-muted">Дней на форуме</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-2">
                        <h5 class="${stats.is_banned ? 'text-danger' : 'text-success'} mb-1">
                            ${stats.is_banned ? 'БАН' : 'OK'}
                        </h5>
                        <small class="text-muted">Статус</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Табы с разными видами активности -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sessions-tab" role="tab">
                    Сессии (${sessions.length})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#topics-tab" role="tab">
                    Темы (${data.recent_topics.length})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#posts-tab" role="tab">
                    Посты (${data.recent_posts.length})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications-tab" role="tab">
                    Уведомления (${data.notifications.length})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#achievements-tab" role="tab">
                    Награды (${data.achievements.length})
                </button>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Вкладка сессий -->
            <div class="tab-pane fade show active" id="sessions-tab" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>IP адрес</th>
                                <th>User Agent</th>
                                <th>Тип</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${sessions.map(session => `
                                <tr>
                                    <td><code>${session.ip_readable}</code></td>
                                    <td class="text-truncate" style="max-width: 200px;" title="${session.user_agent}">
                                        ${session.user_agent.substring(0, 50)}...
                                    </td>
                                    <td>
                                        <span class="badge bg-${session.session_type === 'registration' ? 'success' : 'primary'}">
                                            ${session.session_type === 'registration' ? 'Регистрация' : 'Вход'}
                                        </span>
                                    </td>
                                    <td>${new Date(session.created_at).toLocaleString('ru-RU')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Вкладка тем -->
            <div class="tab-pane fade" id="topics-tab" role="tabpanel">
                <div class="list-group">
                    ${data.recent_topics.map(topic => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${topic.title}</h6>
                                    <p class="mb-1 text-muted">Категория: ${topic.category.name}</p>
                                    <small class="text-muted">${new Date(topic.created_at).toLocaleString('ru-RU')}</small>
                                </div>
                                <div>
                                    <span class="badge bg-primary">${topic.replies_count || 0} ответов</span>
                                    <span class="badge bg-info">${topic.views_count || 0} просмотров</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <!-- Вкладка постов -->
            <div class="tab-pane fade" id="posts-tab" role="tabpanel">
                <div class="list-group">
                    ${data.recent_posts.map(post => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex: 1;">
                                    <h6 class="mb-1">В теме: ${post.topic.title}</h6>
                                    <p class="mb-1">${post.content.substring(0, 100)}...</p>
                                    <small class="text-muted">${new Date(post.created_at).toLocaleString('ru-RU')}</small>
                                </div>
                                <div>
                                    <span class="badge bg-secondary">${post.topic.category.name}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <!-- Вкладка уведомлений -->
            <div class="tab-pane fade" id="notifications-tab" role="tabpanel">
                <div class="list-group">
                    ${data.notifications.map(notification => `
                        <div class="list-group-item ${notification.is_read ? '' : 'list-group-item-info'}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${notification.title}</h6>
                                    <p class="mb-1">${notification.message}</p>
                                    <small class="text-muted">${new Date(notification.created_at).toLocaleString('ru-RU')}</small>
                                </div>
                                <div>
                                    ${notification.is_read ?
                                        '<span class="badge bg-secondary">Прочитано</span>' :
                                        '<span class="badge bg-primary">Новое</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Вкладка наград -->
            <div class="tab-pane fade" id="achievements-tab" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-12">
                        <button class="btn btn-success btn-sm" onclick="showAwardModal(${user.id}, '${user.username}')">
                            <i class="bi bi-trophy me-1"></i>Выдать награду
                        </button>
                    </div>
                </div>
                <div class="row g-3">
                    ${data.achievements.length > 0 ? data.achievements.map(achievement => `
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="${achievement.icon ? '/storage/' + achievement.icon : '/images/achievements/default.png'}"
                                        alt="${achievement.name}"
                                        class="mb-2"
                                        style="max-width: 64px; max-height: 64px;">
                                    <h6 class="mb-1">${achievement.name}</h6>
                                    <p class="text-muted small mb-2">${achievement.description}</p>
                                    <small class="text-muted d-block">
                                        Получена: ${new Date(achievement.pivot.awarded_at).toLocaleString('ru-RU')}
                                    </small>
                                    ${achievement.pivot.awarded_by ? `
                                        <small class="text-muted d-block">
                                            Выдал: ${achievement.awarded_by_user ? achievement.awarded_by_user.username : 'Система'}
                                        </small>
                                    ` : ''}
                                    <button class="btn btn-sm btn-outline-danger mt-2"
                                            onclick="revokeAchievement(${user.id}, ${achievement.id}, '${achievement.name}')">
                                        <i class="bi bi-x-lg"></i> Отозвать
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('') : '<div class="col-12 text-center text-muted py-4">У пользователя пока нет наград</div>'}
                </div>
            </div>
        </div>
    `;
}

// НОВОЕ: Функции массового удаления
function confirmMassDelete(type, username, userId, count) {
    if (count === 0) {
        alert(`У пользователя ${username} нет ${type === 'topics' ? 'тем' : 'постов'} для удаления.`);
        return;
    }

    const typeText = type === 'topics' ? 'темы' : 'посты';
    const typeTextGen = type === 'topics' ? 'тем' : 'постов';
    
    const reason = prompt(`Укажите причину массового удаления ${typeTextGen} пользователя ${username} (необязательно):`);
    
    // Пользователь нажал "Отмена"
    if (reason === null) {
        return;
    }
    
    const confirmUsername = prompt(`ВНИМАНИЕ! Это действие необратимо!\n\nБудет удалено ${count} ${typeTextGen} пользователя ${username}.\n\nДля подтверждения введите имя пользователя: ${username}`);
    
    if (confirmUsername !== username) {
        alert('Неверное подтверждение имени пользователя. Операция отменена.');
        return;
    }
    
    if (!confirm(`Вы АБСОЛЮТНО УВЕРЕНЫ, что хотите удалить ВСЕ ${typeText} пользователя ${username}?\n\nЭто действие НЕОБРАТИМО!`)) {
        return;
    }
    
    // Показываем индикатор загрузки
    const btnElement = event.target;
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Удаление...';
    btnElement.disabled = true;
    
    // Отправляем запрос
    fetch(`/moderation/users/${userId}/${type}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            reason: reason,
            confirm_username: confirmUsername
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Перезагружаем данные активности
            showUserActivity(userId);
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при удалении: ' + error.message);
    })
    .finally(() => {
        // Восстанавливаем кнопку
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    });
}

// Функции для управления наградами
let currentUserId = null;
let currentUsername = '';

function showAwardModal(userId, username) {
    currentUserId = userId;
    currentUsername = username;

    // Загружаем список доступных наград
    fetch('/moderation/achievements/available', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('achievementSelect');
        select.innerHTML = '<option value="">Выберите награду...</option>';

        data.achievements.forEach(achievement => {
            const option = document.createElement('option');
            option.value = achievement.id;
            option.textContent = `${achievement.name} - ${achievement.description}`;
            select.appendChild(option);
        });

        document.getElementById('awardUsername').textContent = username;
        new bootstrap.Modal(document.getElementById('awardAchievementModal')).show();
    });
}

function awardAchievement() {
    const achievementId = document.getElementById('achievementSelect').value;

    if (!achievementId) {
        alert('Выберите награду для выдачи');
        return;
    }

    fetch(`/moderation/achievements/${achievementId}/award`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: currentUserId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.message) {
            alert(data.message || 'Награда успешно выдана');
            bootstrap.Modal.getInstance(document.getElementById('awardAchievementModal')).hide();
            // Обновляем данные активности
            showUserActivity(currentUserId);
        } else {
            alert('Ошибка при выдаче награды');
        }
    })
    .catch(error => {
        alert('Произошла ошибка: ' + error.message);
    });
}

function revokeAchievement(userId, achievementId, achievementName) {
    if (!confirm(`Отозвать награду "${achievementName}"?`)) {
        return;
    }

    fetch(`/moderation/achievements/${achievementId}/revoke`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success || data.message) {
            alert(data.message || 'Награда успешно отозвана');
            // Обновляем данные активности
            showUserActivity(userId);
        } else {
            alert('Ошибка при отзыве награды');
        }
    })
    .catch(error => {
        alert('Произошла ошибка: ' + error.message);
    });
}
</script>
@endif

<!-- Модальное окно для выдачи награды -->
<div class="modal fade" id="awardAchievementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-trophy me-2"></i>Выдать награду
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Выдать награду пользователю <strong id="awardUsername"></strong></p>

                <div class="mb-3">
                    <label class="form-label">Выберите награду</label>
                    <select id="achievementSelect" class="form-select">
                        <option value="">Загрузка...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-success" onclick="awardAchievement()">
                    <i class="bi bi-check-lg me-1"></i>Выдать награду
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно блокировки пользователя -->
<div class="modal fade" id="banUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="banUserForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Заблокировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Заблокировать пользователя <strong id="banUserName"></strong>?</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Причина блокировки *</label>
                        <textarea name="reason" class="form-control" rows="3" 
                                  placeholder="Укажите причину блокировки..." required></textarea>
                        <div class="form-text">Причина будет видна пользователю</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Длительность блокировки *</label>
                        <select name="duration" class="form-select" required>
                            <option value="">Выберите длительность</option>
                            <option value="1h">1 час</option>
                            <option value="24h">24 часа</option>
                            <option value="7d">7 дней</option>
                            <option value="30d">30 дней</option>
                            <option value="permanent">Навсегда</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Заблокированный пользователь не сможет создавать темы, писать посты, ставить лайки и выполнять другие действия на форуме.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-person-x me-1"></i>Заблокировать
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showBanModal(userId, userName) {
    document.getElementById('banUserName').textContent = userName;
    document.getElementById('banUserForm').action = `/moderation/users/${userId}/ban`;
    new bootstrap.Modal(document.getElementById('banUserModal')).show();
}
</script>
@endsection