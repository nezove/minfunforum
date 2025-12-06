@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header Section -->
            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4">
                <div class="d-flex align-items-center mb-3 mb-sm-0">
                    <div>
                        <h2 class="mb-0 fw-bold">Настройки профиля</h2>
                        <p class="text-muted mb-0 small">Управление личной информацией</p>
                    </div>
                </div>
                <a href="{{ route('profile.show', auth()->user()) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>
                    <span class="d-none d-sm-inline">Назад к профилю</span>
                    <span class="d-sm-none">Назад</span>
                </a>
            </div>

            @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="profileForm">
                @csrf
                @method('PUT')

                <!-- Avatar Section - Mobile First -->
                <div class="row g-4">
                    <div class="col-12 order-0 order-lg-1 col-lg-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-image text-success me-2"></i>
                                    <h5 class="mb-0 fw-semibold">Фото профиля</h5>
                                </div>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="position-relative d-inline-block mb-3">
                                    <img id="avatarPreview" src="{{ auth()->user()->avatar_url }}"
                                        class="rounded-circle border border-3 border-primary shadow" width="120"
                                        height="120" alt="Аватар пользователя" style="object-fit: cover;">
                                    <div
                                        class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 shadow-sm">
                                        <i class="bi bi-camera text-white"></i>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="mb-1">{{ auth()->user()->name }}</h6>
                                    <small class="text-muted">{{ auth()->user()->username }}</small>
                                </div>

                                <div class="mb-3">
                                    <label for="avatar" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-cloud-upload me-1"></i>Выбрать новое фото
                                    </label>
                                    <input id="avatar" type="file"
                                        class="form-control d-none @error('avatar') is-invalid @enderror" name="avatar"
                                        accept="image/*">
                                    @error('avatar')
                                    <div class="text-danger small mt-2">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <div class="bg-light rounded p-3">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>Требования:</strong>
                                    </small>
                                    <small class="text-muted">
                                        • Максимум 5MB<br>
                                        • JPG, PNG<br>
                                        • Автоматическое сжатие до 312x312px
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="col-12 order-1 order-lg-0 col-lg-8">
                        <!-- Basic Information Card -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person-circle text-primary me-2"></i>
                                    <h5 class="mb-0 fw-semibold">Основная информация</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <!-- Username (Read-only) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-at me-1"></i>Логин
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control bg-light"
                                                value="{{ auth()->user()->username }}" disabled>
                                            <span class="input-group-text bg-light border-start-0">
                                                <i class="bi bi-lock text-muted"></i>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">Логин нельзя изменить</small>
                                        </div>
                                    </div>

                                    <!-- Email (Read-only) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </label>
                                        <div class="input-group">
                                            <input type="email" class="form-control bg-light"
                                                value="{{ auth()->user()->email }}" disabled>
                                            <span class="input-group-text bg-light border-start-0">
                                                <i class="bi bi-lock text-muted"></i>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">Email нельзя изменить</small>
                                        </div>
                                    </div>

                                    <!-- Display Name -->
                                    <div class="col-12">
                                        <label for="name" class="form-label fw-semibold">
                                            <i class="bi bi-person me-1"></i>Имя пользователя
                                        </label>
                                        <input id="name" type="text"
                                            class="form-control form-control-lg @error('name') is-invalid @enderror"
                                            name="name" value="{{ old('name', auth()->user()->name) }}" required
                                            placeholder="Ваше отображаемое имя">
                                        @error('name')
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                        <div class="form-text">
                                            <small class="text-muted">Это имя будет отображаться в ваших
                                                сообщениях</small>
                                        </div>
                                    </div>

                                    <!-- Bio -->
                                    <div class="col-12">
                                        <label for="bio" class="form-label fw-semibold">
                                            <i class="bi bi-card-text me-1"></i>О себе
                                        </label>
                                        <textarea id="bio" class="form-control @error('bio') is-invalid @enderror"
                                            name="bio" rows="4"
                                            placeholder="Расскажите немного о себе...">{{ old('bio', auth()->user()->bio) }}</textarea>
                                        @error('bio')
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                        <div class="form-text">
                                            <small class="text-muted">Максимум 500 символов</small>
                                        </div>
                                    </div>

                                    <!-- Location and Website -->
                                    <div class="col-12 col-md-6">
                                        <label for="location" class="form-label fw-semibold">
                                            <i class="bi bi-geo-alt me-1"></i>Местоположение
                                        </label>
                                        <input id="location" type="text"
                                            class="form-control @error('location') is-invalid @enderror" name="location"
                                            value="{{ old('location', auth()->user()->location) }}"
                                            placeholder="Город, страна">
                                        @error('location')
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="website" class="form-label fw-semibold">
                                            <i class="bi bi-globe me-1"></i>Веб-сайт
                                        </label>
                                        <input id="website" type="url"
                                            class="form-control @error('website') is-invalid @enderror" name="website"
                                            value="{{ old('website', auth()->user()->website) }}"
                                            placeholder="https://example.com">
                                        @error('website')
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Name Style Card -->
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-palette text-primary me-2"></i>
                                        <h5 class="mb-0 fw-semibold">Стиль никнейма</h5>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="usernameStyleEnabled"
                                               {{ auth()->user()->username_style_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="usernameStyleEnabled">
                                            Включить
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Превью никнейма -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Превью:</label>
                                    <div class="p-3 bg-light rounded text-center">
                                        <span id="usernamePreview" style="{{ auth()->user()->username_style }}">
                                            {{ auth()->user()->username }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Пресеты стилей -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Выберите пресет:</label>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: radial-gradient(#eb10ff, #d700ff); text-shadow: 0px 0px 9px rgba(255, 255, 255, 0.61), -1px -1px 1px rgba(255, 255, 255, 0.61), 1px 0px 7px rgb(204, 0, 255); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                <span style="background: radial-gradient(#eb10ff, #d700ff); text-shadow: 0px 0px 9px rgba(255, 255, 255, 0.61); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                    Фиолетовый
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(20deg, #006eff, #00ff81 52%, #fff 50%, #93cbff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 7px #00ffcf80;">
                                                <span style="background: linear-gradient(20deg, #006eff, #00ff81 52%, #fff 50%, #93cbff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                    Бирюзовый
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="color: #000000; text-shadow: 1px 0px 0px #FACD78, -1px 0px 0px #FACD78, 0px 2px 5px #FACD78, 1px 1px 5px #FACD78;">
                                                <span style="color: #000000; text-shadow: 1px 0px 0px #FACD78, -1px 0px 0px #FACD78, 0px 2px 5px #FACD78;">
                                                    Золотой
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(45deg, #ff0844, #ffb199); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                <span style="background: linear-gradient(45deg, #ff0844, #ffb199); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                    Красный
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(to right, #30cfd0 0%, #330867 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                <span style="background: linear-gradient(to right, #30cfd0 0%, #330867 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                    Синий
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(to right, #f953c6, #b91d73); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 10px rgba(249, 83, 198, 0.5);">
                                                <span style="background: linear-gradient(to right, #f953c6, #b91d73); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                    Розовый
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                    Индиго
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(to right, #fa709a 0%, #fee140 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                <span style="background: linear-gradient(to right, #fa709a 0%, #fee140 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                                    Закат
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="background: linear-gradient(to right, #00b4db, #0083b0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                <span style="background: linear-gradient(to right, #00b4db, #0083b0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold;">
                                                    Океан
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="preset-item p-2 border rounded text-center" style="cursor: pointer;"
                                                 data-style="">
                                                <span style="color: #6c757d;">Сбросить</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Кастомный стиль -->
                                <div class="mb-3">
                                    <label for="customUsernameStyle" class="form-label fw-semibold">
                                        Или введите свой CSS:
                                    </label>
                                    <textarea id="customUsernameStyle" class="form-control font-monospace" rows="3"
                                              placeholder="background: linear-gradient(...); -webkit-background-clip: text; ..."
                                              style="font-size: 0.875rem;">{{ auth()->user()->username_style }}</textarea>
                                    <small class="text-muted">
                                        Разрешены: background, color, text-shadow, font-weight, font-style, text-decoration
                                    </small>
                                </div>

                                <button type="button" class="btn btn-primary" id="saveUsernameStyleBtn">
                                    <i class="bi bi-check-lg me-1"></i>Сохранить стиль
                                </button>
                            </div>
                        </div>

                        <!-- Password Change Card -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-lock text-warning me-2"></i>
                                    <h5 class="mb-0 fw-semibold">Изменение пароля</h5>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-info border-0 bg-info bg-opacity-10">
                                    <div class="d-flex">
                                        <i class="bi bi-info-circle text-info me-2 mt-1"></i>
                                        <div>
                                            <small>Оставьте поля пустыми, если не хотите менять пароль</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="current_password" class="form-label fw-semibold">
                                            <i class="bi bi-key me-1"></i>Текущий пароль
                                        </label>
                                        <div class="input-group">
                                            <input id="current_password" type="password"
                                                class="form-control @error('current_password') is-invalid @enderror"
                                                name="current_password" placeholder="Введите текущий пароль">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePassword('current_password')">
                                                <i class="bi bi-eye" id="current_password_icon"></i>
                                            </button>
                                        </div>
                                        @error('current_password')
                                        <div class="invalid-feedback d-block">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="password" class="form-label fw-semibold">
                                            <i class="bi bi-lock me-1"></i>Новый пароль
                                        </label>
                                        <div class="input-group">
                                            <input id="password" type="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                name="password" placeholder="Новый пароль">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePassword('password')">
                                                <i class="bi bi-eye" id="password_icon"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                        <div class="invalid-feedback d-block">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="password_confirmation" class="form-label fw-semibold">
                                            <i class="bi bi-check-square me-1"></i>Подтвердите пароль
                                        </label>
                                        <div class="input-group">
                                            <input id="password_confirmation" type="password" class="form-control"
                                                name="password_confirmation" placeholder="Повторите новый пароль">
                                            <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePassword('password_confirmation')">
                                                <i class="bi bi-eye" id="password_confirmation_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i>Сохранить изменения
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Modal для обрезки аватарки -->
<div class="modal fade" id="avatarCropModal" tabindex="-1" aria-labelledby="avatarCropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avatarCropModalLabel">
                    <i class="bi bi-crop me-2"></i>Выберите область для аватара
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <p class="text-muted mb-3">Выберите квадратную область изображения для аватара</p>
                    <div class="alert alert-info d-inline-block">
                        <small><i class="bi bi-info-circle me-1"></i>Перетащите синюю область или её углы для
                            изменения</small>
                    </div>
                </div>

                <!-- Контейнер для обрезки -->
                <div class="text-center">
                    <div id="cropImageContainer"
                        class="position-relative d-inline-block border-2 border-primary rounded"
                        style="background: #f8f9fa; max-width: 100%;">
                        <img id="cropImage" src="" alt="Изображение для обрезки"
                            style="display: none; max-width: 500px; max-height: 500px;">

                        <!-- Область обрезки -->
                        <div id="cropSelector"
                            style="display: none; position: absolute; border: 3px solid #007bff; background: rgba(0, 123, 255, 0.15); cursor: move; min-width: 80px; min-height: 80px;">
                            <!-- Угловые маркеры -->
                            <div class="resize-handle" data-direction="nw"
                                style="position: absolute; top: -8px; left: -8px; width: 16px; height: 16px; background: #007bff; cursor: nw-resize; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            </div>
                            <div class="resize-handle" data-direction="ne"
                                style="position: absolute; top: -8px; right: -8px; width: 16px; height: 16px; background: #007bff; cursor: ne-resize; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            </div>
                            <div class="resize-handle" data-direction="sw"
                                style="position: absolute; bottom: -8px; left: -8px; width: 16px; height: 16px; background: #007bff; cursor: sw-resize; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            </div>
                            <div class="resize-handle" data-direction="se"
                                style="position: absolute; bottom: -8px; right: -8px; width: 16px; height: 16px; background: #007bff; cursor: se-resize; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            </div>

                            <!-- Центральная метка для перемещения -->
                            <div
                                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; background: rgba(255,255,255,0.8); border: 2px solid #007bff; border-radius: 50%; cursor: move;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Отмена
                </button>
                <button type="button" class="btn btn-primary" id="applyCrop">
                    <i class="bi bi-check-circle me-1"></i>Сохранить аватар
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cropData = {
        x: 0,
        y: 0,
        width: 200,
        height: 200
    };
    let currentImage = null;
    let isDragging = false;
    let isResizing = false;
    let resizeDirection = null;
    let startMouseX = 0;
    let startMouseY = 0;
    let startCropX = 0;
    let startCropY = 0;
    let startCropWidth = 0;
    let startCropHeight = 0;

    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    const cropModal = new bootstrap.Modal(document.getElementById('avatarCropModal'));
    const cropImage = document.getElementById('cropImage');
    const cropImageContainer = document.getElementById('cropImageContainer');
    const cropSelector = document.getElementById('cropSelector');
    const applyCropBtn = document.getElementById('applyCrop');

    // Обработка выбора файла
    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            // Валидация
            if (file.size > 5 * 1024 * 1024) {
                alert('Размер файла не должен превышать 5MB');
                this.value = '';
                return;
            }

            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert('Поддерживаются только форматы: JPG, PNG');
                this.value = '';
                return;
            }

            loadImageForCrop(file);
        }
    });

    function loadImageForCrop(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            currentImage = new Image();
            currentImage.onload = function() {
                setupCropInterface(e.target.result);
                cropModal.show();
            };
            currentImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function setupCropInterface(imageSrc) {
        cropImage.src = imageSrc;

        cropImage.onload = function() {
            cropImage.style.display = 'block';
            cropSelector.style.display = 'block';

            // БОЛЬШАЯ область для мобильных устройств
            const imageWidth = this.offsetWidth;
            const imageHeight = this.offsetHeight;
            const minSide = Math.min(imageWidth, imageHeight);

            // Увеличиваем размер области - минимум 150px для удобства на мобильных
            let cropSize = Math.max(150, Math.floor(minSide * 0.6));

            // На мобильных устройствах делаем ещё больше
            if (window.innerWidth <= 768) {
                cropSize = Math.max(200, Math.floor(minSide * 0.7));
            }

            // Центрируем квадрат
            cropData = {
                x: Math.floor((imageWidth - cropSize) / 2),
                y: Math.floor((imageHeight - cropSize) / 2),
                width: cropSize,
                height: cropSize
            };

            updateCropSelector();
        };
    }

    function updateCropSelector() {
        cropSelector.style.left = cropData.x + 'px';
        cropSelector.style.top = cropData.y + 'px';
        cropSelector.style.width = cropData.width + 'px';
        cropSelector.style.height = cropData.height + 'px';
    }

    // Обработчики событий мыши И ТАЧ для мобильных
    function getEventCoordinates(e) {
        if (e.touches && e.touches.length > 0) {
            return {
                x: e.touches[0].clientX,
                y: e.touches[0].clientY
            };
        }
        return {
            x: e.clientX,
            y: e.clientY
        };
    }

    function handleStart(e) {
        e.preventDefault();

        const coords = getEventCoordinates(e);

        if (e.target.classList.contains('resize-handle')) {
            isResizing = true;
            resizeDirection = e.target.dataset.direction;
        } else {
            isDragging = true;
        }

        startMouseX = coords.x;
        startMouseY = coords.y;
        startCropX = cropData.x;
        startCropY = cropData.y;
        startCropWidth = cropData.width;
        startCropHeight = cropData.height;
    }

    function handleMove(e) {
        if (!isDragging && !isResizing) return;
        e.preventDefault();

        const coords = getEventCoordinates(e);
        const deltaX = coords.x - startMouseX;
        const deltaY = coords.y - startMouseY;

        if (isDragging) {
            // Перемещение области
            const newX = startCropX + deltaX;
            const newY = startCropY + deltaY;

            const maxX = cropImage.offsetWidth - cropData.width;
            const maxY = cropImage.offsetHeight - cropData.height;

            cropData.x = Math.max(0, Math.min(newX, maxX));
            cropData.y = Math.max(0, Math.min(newY, maxY));

        } else if (isResizing) {
            // Изменение размера - ВСЕГДА квадрат
            let delta = 0;

            if (resizeDirection.includes('e') || resizeDirection.includes('s')) {
                delta = Math.max(deltaX, deltaY);
            } else if (resizeDirection.includes('w') || resizeDirection.includes('n')) {
                delta = Math.min(-deltaX, -deltaY);
            }

            // Минимальный размер увеличен для мобильных
            let newSize = Math.max(80, startCropWidth + delta);

            let newX = startCropX;
            let newY = startCropY;

            if (resizeDirection.includes('w')) {
                newX = startCropX + startCropWidth - newSize;
            }
            if (resizeDirection.includes('n')) {
                newY = startCropY + startCropHeight - newSize;
            }

            const maxX = cropImage.offsetWidth - newSize;
            const maxY = cropImage.offsetHeight - newSize;

            if (newX >= 0 && newX <= maxX && newY >= 0 && newY <= maxY) {
                cropData.x = newX;
                cropData.y = newY;
                cropData.width = newSize;
                cropData.height = newSize;
            }
        }

        updateCropSelector();
    }

    function handleEnd(e) {
        isDragging = false;
        isResizing = false;
        resizeDirection = null;
    }

    // Мышь
    cropSelector.addEventListener('mousedown', handleStart);
    document.addEventListener('mousemove', handleMove);
    document.addEventListener('mouseup', handleEnd);

    // Тач для мобильных
    cropSelector.addEventListener('touchstart', handleStart);
    document.addEventListener('touchmove', handleMove);
    document.addEventListener('touchend', handleEnd);

    // AJAX сохранение аватара
    applyCropBtn.addEventListener('click', function() {
        if (!currentImage) return;

        const originalText = this.innerHTML;
        this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Сохранение...';
        this.disabled = true;

        const canvas = document.createElement('canvas');
        canvas.width = 312;
        canvas.height = 312;
        const ctx = canvas.getContext('2d');

        const scaleX = currentImage.naturalWidth / cropImage.offsetWidth;
        const scaleY = currentImage.naturalHeight / cropImage.offsetHeight;

        const sourceX = cropData.x * scaleX;
        const sourceY = cropData.y * scaleY;
        const sourceWidth = cropData.width * scaleX;
        const sourceHeight = cropData.height * scaleY;

        ctx.drawImage(
            currentImage,
            sourceX, sourceY, sourceWidth, sourceHeight,
            0, 0, 312, 312
        );

        canvas.toBlob(function(blob) {
            // ИСПРАВЛЕННАЯ отправка - добавляем все нужные поля
            const formData = new FormData();

            // Создаем файл из blob с правильным именем и типом
            const file = new File([blob], 'avatar.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            });

            formData.append('avatar', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')
                .getAttribute('content'));
            formData.append('_method', 'PUT');

            // ВАЖНО: Добавляем обязательные поля из формы для валидации
            formData.append('name', document.getElementById('name').value);
            formData.append('bio', document.getElementById('bio').value || '');
            formData.append('location', document.getElementById('location').value || '');
            formData.append('website', document.getElementById('website').value || '');

            fetch('/user', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Сервер вернул некорректный ответ');
                        }
                    });
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        avatarPreview.src = canvas.toDataURL('image/jpeg', 0.9) + '?t=' +
                            Date.now();
                        cropModal.hide();
                        showAlert('success', 'Аватар сохранен в размере 312x312px!');
                    } else {
                        let errorMessage = 'Ошибка при сохранении аватара';

                        if (data.errors) {
                            // Показываем ошибки валидации
                            const errorMessages = Object.values(data.errors).flat();
                            errorMessage = errorMessages.join(', ');
                        } else if (data.message) {
                            errorMessage = data.message;
                        }

                        showAlert('error', errorMessage);
                    }
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    showAlert('error', 'Произошла ошибка: ' + error.message);
                })
                .finally(() => {
                    applyCropBtn.innerHTML = originalText;
                    applyCropBtn.disabled = false;
                });

        }, 'image/jpeg', 0.9);
    });

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 350px;';
        alert.innerHTML = `
            <i class="bi ${iconClass} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 4000);
    }

    document.getElementById('avatarCropModal').addEventListener('hidden.bs.modal', function() {
        cropImage.style.display = 'none';
        cropSelector.style.display = 'none';
        currentImage = null;
        avatarInput.value = '';
    });

    // Работа со стилями никнейма
    const usernamePreview = document.getElementById('usernamePreview');
    const customUsernameStyle = document.getElementById('customUsernameStyle');
    const usernameStyleEnabled = document.getElementById('usernameStyleEnabled');
    const saveUsernameStyleBtn = document.getElementById('saveUsernameStyleBtn');
    const presetItems = document.querySelectorAll('.preset-item');

    // Клик по пресету
    presetItems.forEach(item => {
        item.addEventListener('click', function() {
            const style = this.dataset.style;
            customUsernameStyle.value = style;
            usernamePreview.setAttribute('style', style);
        });
    });

    // Изменение кастомного стиля
    customUsernameStyle.addEventListener('input', function() {
        usernamePreview.setAttribute('style', this.value);
    });

    // Сохранение стиля
    saveUsernameStyleBtn.addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Сохранение...';

        fetch('{{ route("profile.update-username-style") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username_style: customUsernameStyle.value,
                username_style_enabled: usernameStyleEnabled.checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
            } else {
                showAlert('error', data.message || 'Ошибка при сохранении стиля');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Произошла ошибка при сохранении');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
});
</script>

@endsection