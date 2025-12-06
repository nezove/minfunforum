@extends('layouts.app')

@section('title', 'Вход')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Вход в аккаунт
                    </h4>
                </div>

                <div class="card-body p-4">
                    <!-- Предупреждения -->
                    @if(session('warning'))
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf

                        <!-- Email -->
                        <div class="mb-3">
    <label for="username" class="form-label">
        <i class="bi bi-person me-1"></i>Логин или Email
    </label>
    <input id="username" type="text" 
           class="form-control @error('username') is-invalid @enderror" 
           name="username" 
           value="{{ old('username') }}" 
           required 
           autocomplete="username" 
           autofocus
           placeholder="Введите логин или email">

    @error('username')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror
</div>


                        <!-- Пароль -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>Пароль
                            </label>
                            <div class="input-group">
                                <input id="password" type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password"
                                       placeholder="Введите пароль">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Запомнить меня -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                                       {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    Запомнить меня
                                </label>
                            </div>
                        </div>

                        <!-- hCaptcha (показываем только если нужна) -->
                        @if((isset($requiresCaptcha) && $requiresCaptcha) || session('requires_captcha'))
                        <div class="mb-3">
                            <div class="h-captcha"
                                 data-sitekey="{{ config('services.hcaptcha.site_key') }}"
                                 data-theme="light">
                            </div>
                            @error('h-captcha-response')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <!-- Кнопка входа -->
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="loginBtn">
                                <span id="loginBtnText">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Войти
                                </span>
                                <span id="loginBtnLoading" style="display: none;">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Вход...
                                </span>
                            </button>
                        </div>

                        <!-- Ссылки -->
                        <div class="text-center">
                            <div class="row">
                                <div class="col-6">
                                    <a class="btn btn-link text-decoration-none small" 
                                       href="{{ route('password.request') }}">
                                        <i class="bi bi-key me-1"></i>Забыли пароль?
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a class="btn btn-link text-decoration-none small" 
                                       href="{{ route('register') }}">
                                        <i class="bi bi-person-plus me-1"></i>Регистрация
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем hCaptcha только если нужна -->
@if((isset($requiresCaptcha) && $requiresCaptcha) || session('requires_captcha'))
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показать/скрыть пароль
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'text') {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
    }

    // Обработка отправки формы
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    const loginBtnLoading = document.getElementById('loginBtnLoading');

    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            loginBtn.disabled = true;
            loginBtnText.style.display = 'none';
            loginBtnLoading.style.display = 'inline';
        });
    }

    // Автофокус на email если поле пустое
    const emailField = document.getElementById('email');
    if (emailField && !emailField.value) {
        emailField.focus();
    }
});
</script>
@endsection