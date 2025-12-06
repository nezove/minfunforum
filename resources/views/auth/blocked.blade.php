@extends('layouts.app')

@section('title', 'IP заблокирован')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-x me-2"></i>
                        IP адрес заблокирован
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                    </div>

                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>{{ $message }}</strong>
                    </div>

                    @if(isset($unblock_time) && $unblock_time)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Информация о блокировке:</h6>
                            <ul class="list-unstyled">
                                <li><strong>IP адрес:</strong> {{ request()->ip() }}</li>
                                <li><strong>Время блокировки:</strong> 48 часов</li>
                                <li><strong>Причина:</strong> Превышение лимита попыток входа (8+ попыток)</li>
                                <li><strong>Разблокировка:</strong> {{ $unblock_time->format('d.m.Y H:i:s') }}</li>
                                <li><strong>Осталось:</strong> {{ $unblock_time->diffForHumans() }}</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6>
                                    <i class="bi bi-clock me-2"></i>Информация о времени
                                </h6>
                                <p class="mb-1"><strong>Разблокировка:</strong> {{ $unblock_time->format('d.m.Y в H:i:s') }}</p>
                                <p class="mb-0"><strong>Осталось примерно:</strong> {{ $unblock_time->diffForHumans() }}</p>
                                <hr class="my-2">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Обновите страницу, чтобы проверить актуальное время
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Почему это произошло:</h6>
                            <ul class="text-danger">
                                <li>Превышен лимит неудачных попыток входа (8 попыток)</li>
                                <li>Блокировка IP на 48 часов</li>
                                <li>Защита от автоматических атак</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Что делать:</h6>
                            <ul class="text-success">
                                <li>Дождитесь истечения времени блокировки</li>
                                <li>Убедитесь в правильности данных для входа</li>
                                <li>Используйте восстановление пароля при необходимости</li>
                            </ul>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <a href="{{ route('password.request') }}" class="btn btn-primary">
                            <i class="bi bi-key me-2"></i>Восстановить пароль
                        </a>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-2"></i>На главную
                        </a>
                    </div>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Если вы считаете, что блокировка произошла по ошибке, 
                            <a href="mailto:support@example.com" class="text-decoration-none">
                                свяжитесь с поддержкой
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection