@if(auth()->check() && auth()->user()->isBanned() && auth()->user()->getBanType() === 'temporary')
<div class="alert alert-warning border-0 shadow-sm mb-4 position-sticky">
    <div class="row align-items-center">
        <div class="col-md-1 text-center">
            <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" 
                 style="width: 50px; height: 50px;">
                <i class="bi bi-exclamation-triangle text-white"></i>
            </div>
        </div>
        <div class="col-md-11">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="mb-1 text-warning fw-bold">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        Ваш аккаунт временно ограничен
                    </h6>
                    <p class="mb-0">
                        <strong>До:</strong> {{ auth()->user()->banned_until->format('d.m.Y в H:i') }} 
                        <span class="text-muted">({{ auth()->user()->getBanTimeRemaining() }})</span>
                        @if(auth()->user()->ban_reason)
                            <br>
                            <strong>Причина:</strong> {{ auth()->user()->ban_reason }}
                        @endif
                    </p>
                    <small class="text-muted">
                        Вы можете просматривать контент и редактировать профиль, но создание нового контента ограничено.
                    </small>
                </div>
                <div class="ms-3">
                    <a href="{{ route('banned') }}" class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-info-circle me-1"></i>Подробнее
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif