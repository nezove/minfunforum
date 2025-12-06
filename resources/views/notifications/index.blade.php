@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Предупреждение о бане --}}
    @include('components.ban-warning')

    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Хлебные крошки -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">Главная</a></li>
                    <li class="breadcrumb-item active">Уведомления</li>
                </ol>
            </nav>

            <!-- Заголовок и действия -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h2 class="mb-1"><i class="bi bi-bell me-2"></i>Уведомления</h2>
                    <p class="text-muted mb-0">Управляйте вашими уведомлениями</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('notifications.settings') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i>Настройки
                    </a>

                    @if($notifications->count() > 0)
                    @if($notifications->where('is_read', false)->count() > 0)
                    <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-check2-all me-1"></i>Прочитать все
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('notifications.deleteAll') }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Вы уверены, что хотите удалить все уведомления? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Удалить всё
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($notifications->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-bell-slash display-1 text-muted"></i>
                    </div>
                    <h3 class="text-muted mb-2">Уведомлений нет</h3>
                    <p class="text-muted mb-4">Здесь будут отображаться ваши уведомления</p>
                    <a href="{{ route('forum.index') }}" class="btn btn-primary">
                        <i class="bi bi-house me-2"></i>На главную
                    </a>
                </div>
            </div>
            @else
            <!-- Список уведомлений -->
            <div class="list-group shadow-sm">
                @foreach($notifications as $notification)
                <div class="list-group-item list-group-item-action {{ $notification->is_read ? '' : 'list-group-item-primary' }} border-start border-2 {{ $notification->is_read ? 'border-secondary' : 'border-primary' }} p-3">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Аватар пользователя с иконкой -->
                        <div class="position-relative flex-shrink-0">
                            <img src="{{ $notification->fromUser->avatar_url ?? '/storage/avatars/default-avatar.jpg' }}"
                                class="rounded-circle border" width="56" height="56" alt="Avatar"
                                style="object-fit: cover;">
                            <!-- Иконка типа уведомления -->
                            <span class="position-absolute bottom-0 end-0 bg-white rounded-circle d-flex align-items-center justify-content-center border border-2"
                                style="width: 24px; height: 24px; transform: translate(25%, 25%);">
                                <i class="bi {{ $notification->icon }} text-primary" style="font-size: 12px;"></i>
                            </span>
                        </div>

                        <!-- Содержимое уведомления -->
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">{{ $notification->title }}</h6>
                                    <p class="text-muted mb-0">{{ $notification->message }}</p>
                                </div>
                                @if(!$notification->is_read)
                                <span class="badge bg-primary flex-shrink-0">Новое</span>
                                @endif
                            </div>

                            <!-- Время и действия -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </small>

                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('notifications.show', $notification) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Перейти
                                    </a>

                                    @if(!$notification->is_read)
                                    <button onclick="markAsRead({{ $notification->id }})"
                                        class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check me-1"></i>Прочитано
                                    </button>
                                    @endif

                                    <form action="{{ route('notifications.destroy', $notification) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Удалить это уведомление?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Пагинация -->
            @if($notifications->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $notifications->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Готово!', 'Уведомление отмечено как прочитанное');
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
}

function showToast(type, title, message) {
    // Простая реализация toast уведомления
    alert(title + ': ' + message);
}
</script>
@endsection