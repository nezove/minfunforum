@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Регистрация') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}" id="registration-form">
                        @csrf

                        <div class="row mb-3">
                            <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Логин') }}</label>

                            <div class="col-md-6">
                                <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" 
                                       name="username" value="{{ old('username') }}" required autocomplete="username" 
                                       autofocus placeholder="Только латиница, цифры и _">

                                <div id="username-feedback" class="mt-1"></div>

                                @error('username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror

                                <div id="username-suggestions" class="mt-2" style="display: none;">
                                    <small class="text-muted">Предложения:</small>
                                    <div id="suggestions-list"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                       name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Пароль') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                       name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Подтвердите пароль') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" 
                                       name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <!-- Галочка принятия правил -->
                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                           type="checkbox" id="terms_accepted" name="terms_accepted" value="1" 
                                           {{ old('terms_accepted') ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="terms_accepted">
                                        Я принимаю <a href="{{ route('terms') }}" target="_blank" class="text-primary">правила форума</a>
                                    </label>
                                    @error('terms_accepted')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- hCaptcha (показываем только если настроена) -->
                        @if(config('services.hcaptcha.site_key'))
                        <div class="row mb-3">
                            <div class="col-md-6 offset-md-4">
                                <div class="h-captcha" 
                                     data-sitekey="{{ config('services.hcaptcha.site_key') }}"
                                     data-theme="light">
                                </div>
                                @error('h-captcha-response')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        @endif

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary" id="register-btn">
                                    <span id="btn-text">{{ __('Зарегистрироваться') }}</span>
                                    <span id="btn-loading" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- hCaptcha скрипт (загружаем только если настроена) -->
@if(config('services.hcaptcha.site_key'))
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('username');
    const usernameFeedback = document.getElementById('username-feedback');
    const usernameSuggestions = document.getElementById('username-suggestions');
    const suggestionsList = document.getElementById('suggestions-list');
    const form = document.getElementById('registration-form');
    const registerBtn = document.getElementById('register-btn');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    
    let usernameValid = false;

    // Debounce функция для ограничения частоты запросов
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Проверка доступности логина
    const checkUsername = debounce(function(username) {
        if (!username || username.length < 3) {
            usernameFeedback.innerHTML = '';
            usernameSuggestions.style.display = 'none';
            usernameValid = false;
            return;
        }

        fetch('/check-username?username=' + encodeURIComponent(username), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                usernameFeedback.innerHTML = '<small class="text-success"><i class="bi bi-check-circle"></i> ' + data.message + '</small>';
                usernameSuggestions.style.display = 'none';
                usernameValid = true;
            } else {
                usernameFeedback.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</small>';
                
                if (data.suggestions && data.suggestions.length > 0) {
                    suggestionsList.innerHTML = '';
                    data.suggestions.forEach(suggestion => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-outline-primary me-2 mb-1';
                        btn.textContent = suggestion;
                        btn.addEventListener('click', function() {
                            usernameInput.value = suggestion;
                            usernameInput.dispatchEvent(new Event('input'));
                        });
                        suggestionsList.appendChild(btn);
                    });
                    usernameSuggestions.style.display = 'block';
                } else {
                    usernameSuggestions.style.display = 'none';
                }
                usernameValid = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            usernameFeedback.innerHTML = '<small class="text-warning">Ошибка проверки</small>';
            usernameValid = false;
        });
    }, 500);

    // Обработчик ввода логина
    usernameInput.addEventListener('input', function() {
        checkUsername(this.value);
    });

    // Обработчик отправки формы
    form.addEventListener('submit', function(e) {
        // Проверяем логин
        if (!usernameValid && usernameInput.value.trim()) {
            e.preventDefault();
            alert('Пожалуйста, выберите доступный логин');
            return false;
        }
        
        // Проверяем принятие правил
        const termsCheckbox = document.getElementById('terms_accepted');
        if (!termsCheckbox.checked) {
            e.preventDefault();
            alert('Необходимо принять правила форума');
            return false;
        }

        // Проверяем hCaptcha (если присутствует на странице)
        const hcaptchaResponse = document.querySelector('[name="h-captcha-response"]');
        if (hcaptchaResponse && !hcaptchaResponse.value) {
            e.preventDefault();
            alert('Пожалуйста, подтвердите, что вы не робот');
            return false;
        }

        // Показываем индикатор загрузки
        registerBtn.disabled = true;
        btnText.textContent = 'Регистрация...';
        btnLoading.style.display = 'inline-block';
    });

    // Восстанавливаем кнопку при ошибках валидации
    if (document.querySelector('.invalid-feedback')) {
        registerBtn.disabled = false;
        btnText.textContent = 'Зарегистрироваться';
        btnLoading.style.display = 'none';
    }
});
</script>
@endsection