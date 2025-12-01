@extends('layouts.app')

@section('title', 'Аккаунт заблокирован')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-x me-2"></i>
                        Ваш аккаунт заблокирован
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-x text-danger" style="font-size: 4rem;"></i>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Информация о блокировке:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Пользователь:</strong> {{ $user->name }}</li>
                                <li><strong>Дата блокировки:</strong> {{ $user->banned_at->format('d.m.Y H:i') }}</li>
                                <li>
                                    <strong>Тип блокировки:</strong> 
                                    @if($user->getBanType() === 'permanent')
                                        <span class="text-danger">Постоянная</span>
                                    @else
                                        <span class="text-warning">Временная</span>
                                    @endif
                                </li>
                                @if($user->banned_until)
                                    <li><strong>Действует до:</strong> {{ $user->banned_until->format('d.m.Y H:i') }}</li>
                                    <li><strong>Осталось:</strong> {{ $user->getBanTimeRemaining() }}</li>
                                @endif
                                @if($user->bannedByUser)
                                    <li><strong>Заблокирован:</strong> {{ $user->bannedByUser->name }}</li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-md-6">
                            @if($user->ban_reason)
                                <h6>Причина блокировки:</h6>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ $user->ban_reason }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Что вы можете делать:</h6>
                            <ul class="text-success">
                                <li><i class="bi bi-check-circle me-1"></i>Просматривать свой профиль</li>
                                <li><i class="bi bi-check-circle me-1"></i>Редактировать профиль</li>
                                <li><i class="bi bi-check-circle me-1"></i>Выйти из аккаунта</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Что вам запрещено:</h6>
                            <ul class="text-danger">
                                <li><i class="bi bi-x-circle me-1"></i>Создавать новые темы</li>
                                <li><i class="bi bi-x-circle me-1"></i>Отвечать в темах</li>
                                <li><i class="bi bi-x-circle me-1"></i>Ставить лайки</li>
                                <li><i class="bi bi-x-circle me-1"></i>Добавлять в закладки</li>
                                <li><i class="bi bi-x-circle me-1"></i>Другие активные действия</li>
                            </ul>
                        </div>
                    </div>

                    @if($user->getBanType() === 'temporary')
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Временная блокировка!</strong> 
                            Ваш аккаунт будет автоматически разблокирован {{ $user->banned_until->format('d.m.Y в H:i') }}.
                            После этого вы сможете снова пользоваться всеми функциями форума.
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Постоянная блокировка!</strong> 
                            Ваш аккаунт заблокирован навсегда. Для обжалования блокировки обратитесь к администрации.
                        </div>
                    @endif

                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="bi bi-envelope me-2"></i>Связь с администрацией:</h6>
                            <p class="mb-0">
                                Обратитесь к администрации: admin@forum.com
                            </p>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                            <i class="bi bi-person-gear me-1"></i>Редактировать профиль
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-secondary">
                                <i class="bi bi-box-arrow-right me-1"></i>Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if($user->getBanType() === 'temporary')
                <!-- Таймер обратного отсчета для временных банов -->
                <div class="card mt-3">
                    <div class="card-body text-center">
                        <h6>Автоматическая разблокировка через:</h6>
                        <div id="countdown" class="h4 text-primary"></div>
                    </div>
                </div>

                <script>
                // Обратный отсчет до разбана
                const unbanTime = new Date('{{ $user->banned_until->toISOString() }}').getTime();
                
                const countdown = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = unbanTime - now;
                    
                    if (distance < 0) {
                        clearInterval(countdown);
                        document.getElementById("countdown").innerHTML = "Аккаунт разблокирован!";
                        // Перезагрузить страницу через 3 секунды
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    let timeStr = "";
                    if (days > 0) timeStr += days + "д ";
                    if (hours > 0) timeStr += hours + "ч ";
                    if (minutes > 0) timeStr += minutes + "м ";
                    timeStr += seconds + "с";
                    
                    document.getElementById("countdown").innerHTML = timeStr;
                }, 1000);
                </script>
            @endif
        </div>
    </div>
</div>
@endsection