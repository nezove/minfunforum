@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Хлебные крошки -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">Главная</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('notifications.index') }}">Уведомления</a></li>
                    <li class="breadcrumb-item active">Настройки</li>
                </ol>
            </nav>

            <!-- Заголовок -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-gear me-2"></i>Настройки уведомлений</h2>
                <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Назад
                </a>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Выберите типы уведомлений, которые хотите получать</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('notifications.updateSettings') }}" method="POST">
                        @csrf

                        <!-- Уведомления об ответах -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-chat-dots text-primary me-2"></i>Ответы и сообщения
                            </h6>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_reply"
                                       name="notify_reply" {{ $settings->notify_reply ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_reply">
                                    <strong>Новый ответ в моей теме</strong>
                                    <div class="text-muted small">Уведомлять когда кто-то отвечает в моей теме</div>
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_reply_to_post"
                                       name="notify_reply_to_post" {{ $settings->notify_reply_to_post ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_reply_to_post">
                                    <strong>Ответ на мой пост</strong>
                                    <div class="text-muted small">Уведомлять когда кто-то отвечает на мой комментарий</div>
                                </label>
                            </div>
                        </div>

                        <!-- Уведомления об упоминаниях -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-at text-info me-2"></i>Упоминания
                            </h6>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_mention"
                                       name="notify_mention" {{ $settings->notify_mention ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_mention">
                                    <strong>Упоминание в посте</strong>
                                    <div class="text-muted small">Уведомлять когда меня упоминают в комментарии (@username)</div>
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_mention_topic"
                                       name="notify_mention_topic" {{ $settings->notify_mention_topic ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_mention_topic">
                                    <strong>Упоминание в теме</strong>
                                    <div class="text-muted small">Уведомлять когда меня упоминают в теме</div>
                                </label>
                            </div>
                        </div>

                        <!-- Уведомления о лайках -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-heart text-danger me-2"></i>Лайки
                            </h6>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_like_topic"
                                       name="notify_like_topic" {{ $settings->notify_like_topic ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_like_topic">
                                    <strong>Лайк на тему</strong>
                                    <div class="text-muted small">Уведомлять когда кому-то понравилась моя тема</div>
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_like_post"
                                       name="notify_like_post" {{ $settings->notify_like_post ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_like_post">
                                    <strong>Лайк на пост</strong>
                                    <div class="text-muted small">Уведомлять когда кому-то понравился мой комментарий</div>
                                </label>
                            </div>
                        </div>

                        <!-- Уведомления стены -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="bi bi-journal-text text-success me-2"></i>Стена профиля
                            </h6>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_wall_post"
                                       name="notify_wall_post" {{ $settings->notify_wall_post ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_wall_post">
                                    <strong>Запись на моей стене</strong>
                                    <div class="text-muted small">Уведомлять когда кто-то публикует запись на моей стене</div>
                                </label>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_wall_comment"
                                       name="notify_wall_comment" {{ $settings->notify_wall_comment ? 'checked' : '' }}>
                                <label class="form-check-label" for="notify_wall_comment">
                                    <strong>Комментарий к моему посту на стене</strong>
                                    <div class="text-muted small">Уведомлять когда кто-то комментирует мой пост на стене</div>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Сохранить настройки
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Информационная карточка -->
            <div class="card mt-4 border-info">
                <div class="card-body">
                    <h6 class="card-title"><i class="bi bi-info-circle text-info me-2"></i>О настройках уведомлений</h6>
                    <p class="card-text small text-muted mb-0">
                        Вы можете в любой момент изменить типы уведомлений, которые хотите получать.
                        Отключение уведомлений не удалит уже существующие, но вы перестанете получать новые уведомления выбранных типов.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
