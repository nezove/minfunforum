@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-key me-2"></i>
                        Сброс пароля
                    </h4>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="text-muted mb-4">
                        Забыли пароль? Введите ваш email адрес, и мы отправим вам ссылку для сброса пароля.
                    </p>

                    <form method="POST" action="{{ route('password.email') }}" id="reset-form">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope me-1"></i>
                                Email адрес
                            </label>
                            <input id="email" type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autocomplete="email" 
                                   autofocus
                                   placeholder="Введите ваш email">

                            @error('email')
                                <div class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>

                        <!-- hCaptcha - показываем только если настроена -->
                        @if(config('services.hcaptcha.site_key'))
                            <div class="mb-3">
                                <label class="form-label">Проверка безопасности:</label>
                                <div class="h-captcha" 
                                     data-sitekey="{{ config('services.hcaptcha.site_key') }}"
                                     data-theme="light"
                                     data-size="normal"
                                     data-callback="onCaptchaSuccess"
                                     data-expired-callback="onCaptchaExpired"
                                     data-error-callback="onCaptchaError">
                                </div>
                                
                                <!-- Показываем ТОЛЬКО одну ошибку капчи -->
                                @error('h-captcha-response')
                                    <div class="text-danger mt-2">
                                        <small><strong>{{ $message }}</strong></small>
                                    </div>
                                @enderror
                            </div>
                        @endif

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="bi bi-send me-2"></i>
                                <span id="btn-text">Отправить ссылку для сброса</span>
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

@if(config('services.hcaptcha.site_key'))
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>

<script>
let captchaPassed = false;
const submitBtn = document.getElementById('submit-btn');
const btnText = document.getElementById('btn-text');

// Блокируем отправку формы пока не пройдена капча
document.addEventListener('DOMContentLoaded', function() {
    submitBtn.disabled = true;
    btnText.textContent = 'Пройдите проверку безопасности';
});

// Callback при успешном прохождении капчи
function onCaptchaSuccess(token) {
    console.log('✅ hCaptcha пройдена');
    captchaPassed = true;
    submitBtn.disabled = false;
    btnText.textContent = 'Отправить ссылку для сброса';
    submitBtn.classList.remove('btn-secondary');
    submitBtn.classList.add('btn-primary');
}

// Callback при истечении срока капчи
function onCaptchaExpired() {
    console.log('⏰ hCaptcha истекла');
    captchaPassed = false;
    submitBtn.disabled = true;
    btnText.textContent = 'Пройдите проверку безопасности';
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-secondary');
}

// Callback при ошибке капчи
function onCaptchaError(error) {
    console.error('❌ Ошибка hCaptcha:', error);
    captchaPassed = false;
    submitBtn.disabled = true;
    btnText.textContent = 'Ошибка капчи - попробуйте еще раз';
    submitBtn.classList.remove('btn-primary');
    submitBtn.classList.add('btn-danger');
}

// Дополнительная проверка при отправке формы
document.getElementById('reset-form').addEventListener('submit', function(e) {
    if (!captchaPassed) {
        e.preventDefault();
        alert('Пожалуйста, пройдите проверку безопасности');
        return false;
    }
    
    // Показываем индикатор загрузки
    submitBtn.disabled = true;
    btnText.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Отправляется...';
});
</script>
@endif
@endsection