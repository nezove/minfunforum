@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Предупреждение о бане --}}
    @include('components.ban-warning')

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bell me-2"></i>Уведомления</h2>

                @if($notifications->count() > 0)
                <div class="btn-group">
                    <form action="{{ route('notifications.markAllAsRead') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="bi bi-check2-all me-1"></i>Прочитать все
                        </button>
                    </form>

                    <form action="{{ route('notifications.deleteAll') }}" method="POST" class="d-inline"
                        onsubmit="return confirm('Вы уверены, что хотите удалить все уведомления? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i>Удалить всё
                        </button>
                    </form>
                </div>
                @endif
            </div>

            @if($notifications->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-bell-slash display-1 text-muted"></i>
                <h3 class="mt-3 text-muted">Уведомлений нет</h3>
                <p class="text-muted">Здесь будут отображаться ваши уведомления</p>
            </div>
            @else
            <div class="card">
                <div class="card-body p-0">
                    @foreach($notifications as $notification)
                    <div
                        class="p-3 border-bottom {{ $notification->is_read ? '' : 'bg-light' }} border-start border-3 {{ $notification->is_read ? 'border-transparent' : 'border-primary' }}">
                        <div class="d-flex align-items-start">
                            <!-- Аватар пользователя с иконкой в углу -->
                            <div class="position-relative me-3 flex-shrink-0">
                                <img src="{{ $notification->fromUser->avatar_url ?? '/images/default-avatar.png' }}"
                                    class="rounded-circle" width="50" height="50" alt="Avatar"
                                    style="object-fit: cover;">
                                <!-- Иконка уведомления в углу аватара -->
                                <span
                                    class="position-absolute bottom-0 end-0 bg-white rounded-circle d-flex align-items-center justify-content-center border"
                                    style="width: 20px; height: 20px; transform: translate(25%, 25%);">
                                    <i class="bi {{ $notification->icon }} text-primary" style="font-size: 10px;"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $notification->title }}</h6>
                                        <div class="text-muted mb-2">
                                            {{ $notification->message }}
                                        </div>
                                    </div>
                                    @if(!$notification->is_read)
                                    <span class="badge bg-primary">Новое</span>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        {{ $notification->created_at->diffForHumans() }}
                                    </small>

                                    <div>
                                        <a href="{{ route('notifications.show', $notification) }}"
                                            class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-eye me-1"></i>Перейти
                                        </a>

                                        @if(!$notification->is_read)
                                        <button onclick="markAsRead({{ $notification->id }})"
                                            class="btn btn-sm btn-outline-secondary me-2">
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
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $notifications->links() }}
            </div>
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