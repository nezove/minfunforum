@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-shield-lock me-2"></i>
                        Новый пароль
                    </h4>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">
                        Введите новый пароль для вашего аккаунта.
                    </p>

                    <form method="POST" action="{{ route('password.update') }}" id="reset-password-form">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email адрес
                            </label>
                            <input id="email" type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" 
                                   value="{{ $email ?? old('email') }}" 
                                   required 
                                   autocomplete="email"
                                   readonly
                                   style="background-color: #f8f9fa;">

                            @error('email')
                                <div class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock me-1"></i>
                                Новый пароль
                            </label>
                            <input id="password" type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   name="password" 
                                   required 
                                   autocomplete="new-password"
                                   minlength="8"
                                   placeholder="Минимум 8 символов">

                            @error('password')
                                <div class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                            
                            <div class="form-text">
                                Пароль должен содержать минимум 8 символов
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>
                                Подтвердите пароль
                            </label>
                            <input id="password-confirm" type="password" 
                                   class="form-control" 
                                   name="password_confirmation" 
                                   required 
                                   autocomplete="new-password"
                                   minlength="8"
                                   placeholder="Повторите новый пароль">
                            
                            <div class="form-text">
                                Пароли должны совпадать
                            </div>
                        </div>

                        <!-- Индикатор силы пароля -->
                        <div class="mb-3">
                            <div class="password-strength">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="password-strength-bar" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password-strength-text" class="text-muted">
                                    Введите пароль для проверки
                                </small>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="submit-btn">
                                <i class="bi bi-check-circle me-2"></i>
                                Изменить пароль
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>
                                Вернуться к входу
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ТОЛЬКО локальный JavaScript - никаких сторонних скриптов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password-confirm');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('reset-password-form');

    // Проверка силы пароля
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = [];

        if (password.length >= 8) {
            strength += 25;
        } else {
            feedback.push('минимум 8 символов');
        }

        if (/[a-z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('строчные буквы');
        }

        if (/[A-Z]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('заглавные буквы');
        }

        if (/[0-9]/.test(password)) {
            strength += 25;
        } else {
            feedback.push('цифры');
        }

        return { strength, feedback };
    }

    // Обновление индикатора силы пароля
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const result = checkPasswordStrength(password);
        
        strengthBar.style.width = result.strength + '%';
        
        if (result.strength < 50) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Слабый пароль. Добавьте: ' + result.feedback.join(', ');
            strengthText.className = 'text-danger';
        } else if (result.strength < 75) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Средний пароль';
            strengthText.className = 'text-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Надежный пароль';
            strengthText.className = 'text-success';
        }
    });

    // Проверка совпадения паролей
    function checkPasswordMatch() {
        if (confirmInput.value && passwordInput.value !== confirmInput.value) {
            confirmInput.setCustomValidity('Пароли не совпадают');
            confirmInput.classList.add('is-invalid');
        } else {
            confirmInput.setCustomValidity('');
            confirmInput.classList.remove('is-invalid');
        }
    }

    confirmInput.addEventListener('input', checkPasswordMatch);
    passwordInput.addEventListener('input', checkPasswordMatch);

    // Обработка отправки формы
    form.addEventListener('submit', function(e) {
        if (passwordInput.value !== confirmInput.value) {
            e.preventDefault();
            alert('Пароли не совпадают!');
            return;
        }

        // Показываем индикатор загрузки
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Обновляется...';
    });
});
</script>
@endsection