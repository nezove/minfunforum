@extends('layouts.app')

@section('title', 'Доступ запрещен')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-75 align-items-center">
        <div class="col-lg-6 col-md-8 text-center">
            <!-- Анимированная иконка блокировки -->
            <div class="access-denied-animation mb-4">
                <div class="shield-container">
                    <i class="bi bi-shield-x text-warning"></i>
                    <div class="access-denied-text">403</div>
                </div>
            </div>

            <!-- Заголовок -->
            <h1 class="display-4 fw-bold text-warning mb-3">Доступ запрещен</h1>
            
            <!-- Описание -->
            <p class="lead text-muted mb-4">
                У вас нет прав для просмотра этой страницы или выполнения данного действия.
            </p>

            <!-- Возможные причины -->
            <div class="alert alert-light border-0 shadow-sm mb-4">
                <h6 class="alert-heading mb-3">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    Возможные причины:
                </h6>
                <ul class="list-unstyled mb-0 text-start">
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Необходимо войти в систему
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Недостаточно прав доступа
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Аккаунт заблокирован или ограничен
                    </li>
                    <li>
                        <i class="bi bi-arrow-right text-muted me-2"></i>
                        Контент доступен только модераторам
                    </li>
                </ul>
            </div>

            @guest
            <!-- Если пользователь не авторизован -->
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-info-circle me-2"></i>
                    Требуется авторизация
                </h6>
                <p class="mb-0">
                    Для доступа к этой странице необходимо войти в систему.
                </p>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('login') }}" class="btn btn-warning btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Войти
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-person-plus me-2"></i>
                    Регистрация
                </a>
                <a href="{{ route('forum.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-house me-2"></i>
                    На главную
                </a>
            </div>
            @else
            <!-- Если пользователь авторизован, но нет прав -->
            @if(auth()->user()->isBanned())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-shield-x me-2"></i>
                    Аккаунт заблокирован
                </h6>
                <p class="mb-2">
                    Ваш аккаунт заблокирован до: 
                    <strong>
                        {{ auth()->user()->banned_until ? auth()->user()->banned_until->format('d.m.Y H:i') : 'навсегда' }}
                    </strong>
                </p>
                @if(auth()->user()->ban_reason)
                <p class="mb-0">
                    <strong>Причина:</strong> {{ auth()->user()->ban_reason }}
                </p>
                @endif
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ route('banned') }}" class="btn btn-danger btn-lg">
                    <i class="bi bi-info-circle me-2"></i>
                    Подробнее о блокировке
                </a>
                <a href="{{ route('forum.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-house me-2"></i>
                    На главную
                </a>
            </div>
            @else
            <!-- Обычная ошибка доступа -->
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <h6 class="alert-heading">
                    <i class="bi bi-key me-2"></i>
                    Недостаточно прав
                </h6>
                <p class="mb-0">
                    У вас нет необходимых прав для доступа к этому разделу.
                    Обратитесь к администратору, если считаете это ошибкой.
                </p>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <button onclick="history.back()" class="btn btn-warning btn-lg">
                    <i class="bi bi-arrow-left me-2"></i>
                    Назад
                </button>
                <a href="{{ route('forum.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-house me-2"></i>
                    На главную
                </a>
                <a href="{{ route('profile.show', auth()->id()) }}" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-person me-2"></i>
                    Мой профиль
                </a>
            </div>
            @endif
            @endguest

            <!-- Контакты -->
            <div class="mt-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-question-circle me-2"></i>
                            Есть вопросы?
                        </h6>
                        <p class="text-muted mb-3">
                            Если вы считаете, что это ошибка, свяжитесь с администрацией:
                        </p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <a href="mailto:admin@forum.com" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i>
                                Написать админу
                            </a>
                            <a href="#" class="btn btn-outline-info btn-sm">
                                <i class="bi bi-chat-dots me-1"></i>
                                Поддержка
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

.access-denied-animation {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.shield-container {
    position: relative;
    font-size: 5rem;
    animation: shieldPulse 2s infinite;
}

.access-denied-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: bold;
    color: #ffffff;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

@keyframes shieldPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
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