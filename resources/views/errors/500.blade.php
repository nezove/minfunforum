@extends('layouts.app')

@section('title', 'Внутренняя ошибка сервера')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-75 align-items-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Анимированная иконка сервера -->
            <div class="server-error-animation mb-4">
                <div class="server-icon">
                    <i class="bi bi-server text-danger"></i>
                    <div class="error-sparks">
                        <span class="spark spark-1"></span>
                        <span class="spark spark-2"></span>
                        <span class="spark spark-3"></span>
                    </div>
                </div>
                <div class="error-code">500</div>
            </div>

            <!-- Заголовок -->
            <h1 class="display-4 fw-bold text-danger mb-3">Упс! Что-то пошло не так</h1>
            
            <!-- Описание -->
            <p class="lead text-muted mb-4">
                На сервере произошла внутренняя ошибка. Наши разработчики уже уведомлены 
                и работают над устранением проблемы.
            </p>

            <!-- Информация об ошибке -->
            <div class="alert alert-light border-0 shadow-sm mb-4">
                <h6 class="alert-heading mb-3">
                    <i class="bi bi-info-circle text-info me-2"></i>
                    Что произошло:
                </h6>
                <p class="mb-3">Сервер не смог обработать ваш запрос из-за внутренней ошибки.</p>
                <div class="row text-start">
                    <div class="col-md-6">
                        <strong>Что можно сделать:</strong>
                        <ul class="list-unstyled mt-2">
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Попробовать позже
                            </li>
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Обновить страницу
                            </li>
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Очистить кеш браузера
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Если проблема повторяется:</strong>
                        <ul class="list-unstyled mt-2">
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Сообщите администрации
                            </li>
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Опишите ваши действия
                            </li>
                            <li class="mb-1">
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                Укажите время ошибки
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Техническая информация -->
            @if(config('app.debug'))
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-code-slash me-2"></i>
                    Техническая информация (режим отладки):
                </h6>
                <div class="text-start">
                    <small class="text-muted">
                        <strong>Время:</strong> {{ now()->format('d.m.Y H:i:s') }}<br>
                        <strong>URL:</strong> {{ request()->fullUrl() }}<br>
                        <strong>User Agent:</strong> {{ request()->userAgent() }}
                    </small>
                </div>
            </div>
            @endif

            <!-- Кнопки действий -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <button onclick="location.reload()" class="btn btn-danger btn-lg">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Попробовать снова
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

            <!-- Контакты поддержки -->
            <div class="mt-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-headset me-2"></i>
                            Нужна помощь?
                        </h6>
                        <p class="text-muted mb-3">
                            Если проблема не исчезает, свяжитесь с нами:
                        </p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="mailto:support@forum.com" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i>
                                Email поддержки
                            </a>
                            <a href="#" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-telegram me-1"></i>
                                Telegram
                            </a>
                            <a href="#" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-bug me-1"></i>
                                Сообщить об ошибке
                            </a>
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

.server-error-animation {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.server-icon {
    position: relative;
    font-size: 5rem;
    animation: serverShake 3s infinite;
}

.error-sparks {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.spark {
    position: absolute;
    width: 4px;
    height: 4px;
    background: #dc3545;
    border-radius: 50%;
    animation: sparkle 2s infinite;
}

.spark-1 {
    top: 20%;
    right: 10%;
    animation-delay: 0s;
}

.spark-2 {
    top: 60%;
    left: 15%;
    animation-delay: 0.7s;
}

.spark-3 {
    top: 40%;
    right: 20%;
    animation-delay: 1.4s;
}

.error-code {
    font-size: 3rem;
    font-weight: bold;
    color: #dc3545;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

@keyframes serverShake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
    20%, 40%, 60%, 80% { transform: translateX(2px); }
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: scale(1) rotate(180deg);
    }
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