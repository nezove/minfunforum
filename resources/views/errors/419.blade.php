@extends('layouts.app')

@section('title', 'Сессия истекла')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-75 align-items-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Анимированные часы -->
            <div class="session-expired-animation mb-4">
                <div class="clock-container">
                    <i class="bi bi-clock text-info"></i>
                    <div class="clock-hands">
                        <div class="hour-hand"></div>
                        <div class="minute-hand"></div>
                    </div>
                </div>
                <div class="error-code">419</div>
            </div>

            <!-- Заголовок -->
            <h1 class="display-4 fw-bold text-info mb-3">Сессия истекла</h1>
            
            <!-- Описание -->
            <p class="lead text-muted mb-4">
                Ваша сессия истекла из соображений безопасности. 
                Пожалуйста, обновите страницу и повторите действие.
            </p>

            <!-- Объяснение -->
            <div class="alert alert-light border-0 shadow-sm mb-4">
                <h6 class="alert-heading mb-3">
                    <i class="bi bi-shield-check text-success me-2"></i>
                    Почему это произошло:
                </h6>
                <ul class="list-unstyled mb-0 text-start">
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Вы долго не проявляли активность
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Токен безопасности устарел
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Защита от CSRF-атак
                    </li>
                    <li>
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Изменились настройки сервера
                    </li>
                </ul>
            </div>

            <!-- Кнопки действий -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mb-4">
                <button onclick="location.reload()" class="btn btn-info btn-lg">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Обновить страницу
                </button>
                <a href="{{ route('forum.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-house me-2"></i>
                    На главную
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Назад
                </button>
            </div>

            <!-- Советы -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-lightbulb text-warning me-2"></i>
                        Как избежать этой ошибки:
                    </h6>
                    <div class="row text-start">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Не оставляйте формы открытыми надолго
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Периодически обновляйте страницу
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Не используйте кнопку "Назад" в браузере
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    Сохраняйте черновики перед отправкой
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.min-vh-75 {
    min-height: 75vh;
}

.session-expired-animation {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.clock-container {
    position: relative;
    font-size: 5rem;
    color: #17a2b8;
}

.clock-hands {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.hour-hand, .minute-hand {
    position: absolute;
    background: #17a2b8;
    transform-origin: bottom center;
}

.hour-hand {
    width: 3px;
    height: 20px;
    top: -20px;
    left: -1.5px;
    animation: hourRotate 12s infinite linear;
}

.minute-hand {
    width: 2px;
    height: 30px;
    top: -30px;
    left: -1px;
    animation: minuteRotate 1s infinite linear;
}

.error-code {
    font-size: 2.5rem;
    font-weight: bold;
    color: #17a2b8;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

@keyframes hourRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes minuteRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}
</style>
@endsection