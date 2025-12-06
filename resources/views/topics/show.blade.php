@extends('layouts.app')

@section('content')
<!--  resources\views\topics\show.blade.php -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">Главная</a></li>
        <li class="breadcrumb-item"><a
                href="{{ route('forum.category', $topic->category->id) }}">{{ $topic->category->name }}</a></li>
        <li class="breadcrumb-item active">{{ $topic->title }}</li>
    </ol>
</nav>

<div class="row">
    <!-- Основной контент -->
    <div class="col-lg-9">
        <!-- Основной пост темы -->
        <div class="bg-white rounded p-4 mb-4" style="border: 1px solid #dee2e6;" id="topic-main">
            <!-- Заголовок и кнопки действий -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start mb-3">
                <h1 class="h4 mb-0 flex-grow-1">{{ $topic->title }}</h1>

                @auth
                <div class="d-flex flex-wrap gap-2 align-items-center mt-2 mt-sm-0">
                        <!-- Лайк темы -->
    @if(!auth()->user()->isBanned())
        @if($topic->user_id !== auth()->id())
            {{-- Показываем кнопку лайка только если пользователь НЕ автор темы --}}
            <button class="btn btn-outline-primary btn-sm like-topic-btn"
                    data-topic-id="{{ $topic->id }}"
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-placement="top"
                    data-bs-html="true"
                    data-like-type="topic"
                    data-like-id="{{ $topic->id }}">
                <i class="bi bi-heart{{ $topic->isLikedBy(auth()->id()) ? '-fill text-danger' : '' }}"></i>
                <span class="like-count ms-1">{{ $topic->likes_count }}</span>
            </button>
        @else
            {{-- Для автора показываем кнопку с просмотром, но без возможности лайкать --}}
            <button class="btn btn-outline-secondary btn-sm like-topic-btn"
                    data-topic-id="{{ $topic->id }}"
                    data-bs-toggle="popover"
                    data-bs-trigger="hover"
                    data-bs-placement="top"
                    data-bs-html="true"
                    data-like-type="topic"
                    data-like-id="{{ $topic->id }}"
                    data-is-author="true"
                    title="Посмотреть, кто лайкнул">
                <i class="bi bi-heart"></i>
                <span class="like-count ms-1">{{ $topic->likes_count }}</span>
            </button>
        @endif
    @else
        <button class="btn btn-outline-secondary btn-sm" disabled title="Ваш аккаунт ограничен">
            <i class="bi bi-heart"></i>
            <span class="like-count ms-1">{{ $topic->likes_count }}</span>
        </button>
                    @endif

                    <!-- Объединенное меню действий -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots"></i>
                            <span class="d-none d-md-inline ms-1">Действия</span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <!-- Закладки (доступно всем авторизованным) -->
                            <li>
                                <button class="dropdown-item bookmark-topic-btn" data-topic-id="{{ $topic->id }}">
                                    <i
                                        class="bi bi-bookmark{{ $topic->isBookmarkedBy(auth()->id()) ? '-fill' : '' }} me-2"></i>
                                    {{ $topic->isBookmarkedBy(auth()->id()) ? 'Убрать закладку' : 'В закладки' }}
                                </button>
                            </li>

                            @if(auth()->user()->id === $topic->user_id || auth()->user()->hasRole('admin'))
                            <!-- Действия автора/администратора -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header text-muted small">
                                    <i class="bi bi-person me-1"></i>Управление темой
                                </h6>
                            </li>
                            <li>
                                @if($topic->canEdit())
                                <a href="{{ route('topics.edit', $topic) }}" class="dropdown-item">
                                    <i class="bi bi-pencil"></i> Редактировать
                                </a>
                                @endif
                            </li>
                            <li>
                                @if($topic->canDelete())
                                <form method="POST" action="{{ route('topics.destroy', $topic) }}"
                                    style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item"
                                        onclick="return confirm('Вы уверены, что хотите удалить эту тему?')">
                                        <i class="bi bi-trash"></i> Удалить
                                    </button>
                                </form>
                                @endif
                            </li>
                            @endif

                            @if(auth()->check() && auth()->user()->canModerate())
                            <!-- Действия модератора -->
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <h6 class="dropdown-header text-warning small">
                                    <i class="bi bi-gear me-1"></i>Модерация
                                </h6>
                            </li>
                            <li>
                                <form action="{{ route('moderation.topic.toggle-status', $topic) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-{{ $topic->is_closed ? 'unlock' : 'lock' }} me-2"></i>
                                        {{ $topic->is_closed ? 'Открыть тему' : 'Закрыть тему' }}
                                    </button>
                                </form>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#"
                                    onclick="showMoveTopicModal({{ $topic->id }}, '{{ $topic->title }}')">
                                    <i class="bi bi-arrow-right me-2"></i>Переместить тему
                                </a>
                            </li>
                            <li>
                                <button class="dropdown-item text-danger"
                                    onclick="showDeleteTopicModal({{ $topic->id }}, '{{ $topic->title }}')">
                                    <i class="bi bi-trash me-2"></i>Удалить тему
                                </button>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
                @endauth
            </div>
            <!-- Автор и дата -->
            <div class="d-flex align-items-center mb-3">
                @if($topic->user->avatar_url)
                <img src="{{ e($topic->user->avatar_url) }}" alt="Avatar" class="rounded-circle me-2" width="32"
                    height="32" style="object-fit: cover;">
                @else
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2"
                    style="width: 32px; height: 32px;">
                    <i class="bi bi-person text-white"></i>
                </div>
                @endif
                <div>
                    <a href="{{ route('profile.show', $topic->user) }}"
                        class="text-decoration-none text-primary fw-semibold">{!! $topic->user->styled_username !!}</a>
                    @if($topic->user->role === 'admin')
                    <span class="badge bg-danger ms-1">Админ</span>
                    @endif
                    <div class="small text-muted">{{ $topic->created_at->diffForHumans() }}</div>
                </div>
            </div>

            <!-- Содержимое темы -->
            <!-- Содержимое темы - найдите эту секцию в show.blade.php и замените -->
            <div class="topic-content mb-3">
                {!! \App\Helpers\ContentProcessor::processContent($topic->content) !!}
            </div>
            @if($topic->edited_text)
            <small class="text-muted ms-2">
                <i class="bi bi-pencil"></i>
                {{ $topic->edited_text }}
            </small>
            @endif
            @if($topic->tags->count() > 0)
            <div class="mb-3">
                @foreach($topic->tags as $tag)
                <a href="{{ route('tags.show', ['category' => $topic->category->id, 'tag' => $tag->slug]) }}"
                    class="badge text-decoration-none me-1" style="background-color: {{ $tag->color }}; color: white;">
                    {{ $tag->name }}
                </a>
                @endforeach
            </div>
            @endif
            <!-- Галерея изображений темы -->
            @if($topic->galleryImages && $topic->galleryImages->count() > 0)
            <div class="mt-4">
                <h6><i class="bi bi-images me-2"></i>Галерея изображений:</h6>

                <!-- Bootstrap 5 Carousel -->
                <div id="topicGalleryCarousel" class="carousel slide border rounded" data-bs-ride="false">
                    <!-- Индикаторы -->
                    @if($topic->galleryImages->count() > 1)
                    <div class="carousel-indicators">
                        @foreach($topic->galleryImages as $index => $image)
                        <button type="button" data-bs-target="#topicGalleryCarousel" data-bs-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}" aria-label="Слайд {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    @endif

                    <!-- Слайды -->
                    <div class="carousel-inner">
                        @foreach($topic->galleryImages as $index => $image)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" data-slide-index="{{ $index }}">
                            <div class="position-relative bg-dark d-flex align-items-center justify-content-center"
                                style="height: 500px; cursor: pointer;"
                                onclick="openGalleryModal({{ $index }})">
                                <img src="{{ $image->image_url }}" class="d-block img-fluid"
                                    style="max-height: 500px; max-width: 100%; object-fit: contain;"
                                    alt="{{ $image->original_name }}" loading="lazy">

                                <!-- Описание изображения -->
                                @if($image->description)
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2">
                                    <p class="mb-0">{{ $image->description }}</p>
                                </div>
                                @endif

                                <!-- Иконка полноэкранного режима -->
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-dark bg-opacity-75">
                                        <i class="bi bi-arrows-fullscreen"></i> Открыть
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Кнопки навигации -->
                    @if($topic->galleryImages->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#topicGalleryCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Предыдущий</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#topicGalleryCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Следующий</span>
                    </button>
                    @endif
                </div>

                <!-- Миниатюры под каруселью -->
                @if($topic->galleryImages->count() > 1)
                <div class="row mt-3">
                    @foreach($topic->galleryImages as $index => $image)
                    <div class="col-2 col-md-1 mb-2">
                        <img src="{{ $image->thumbnail_url }}"
                            class="img-fluid rounded border gallery-thumbnail {{ $index === 0 ? 'border-primary' : '' }}"
                            style="cursor: pointer; height: 60px; width: 100%; object-fit: cover;"
                            onclick="goToSlide({{ $index }})" alt="Миниатюра {{ $index + 1 }}">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
            <!-- Прикрепленные файлы темы -->
            @if($topic->files && $topic->files->count() > 0)
            <div class="mt-3">
                <h6><i class="bi bi-paperclip me-2"></i>Прикреплённые файлы:</h6>
                <div class="row">
                    @foreach($topic->files as $file)
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark text-primary me-3 fs-3"></i>
                                    <div class="flex-grow-1">
                                        <strong>{{ $file->original_name }}</strong><br>
                                        <small class="text-muted">
                                            {{ $file->formatted_size }}
                                            @if($file->downloads_count > 0)
                                            • <i class="bi bi-download"></i> {{ $file->downloads_count }} {{ $file->downloads_count === 1 ? 'скачивание' : 'скачиваний' }}
                                            @endif
                                        </small>
                                    </div>
                                    <div class="ms-2">
                                        <a href="{{ e($file->download_url) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Скачать
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Статистика темы -->
            <div class="row mt-4 pt-3 border-top text-center">
                <div class="col-3">
                    <small class="text-muted d-block">Просмотры</small>
                    <strong>{{ $topic->views ?? 0 }}</strong>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block">Ответы</small>
                    <strong>{{ $topic->replies_count ?? 0 }}</strong>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block">Лайки</small>
                    <strong>{{ $topic->likes_count ?? 0 }}</strong>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block">Участников</small>
                    <strong>{{ $topic->participants_count ?? 1 }}</strong>
                </div>
            </div>
        </div>

        <!-- Ответы -->
        <div class="card mb-4" id="posts-container">
            <div class="card-header">
                <h5 class="mb-0">Ответы (<span class="replies-count">{{ $topic->replies_count ?? 0 }}</span>)</h5>
            </div>

            <div id="posts-list">
                @if($posts->isEmpty())
                <!-- Сообщение когда ответов нет -->
                <div class="p-4 text-center text-muted" id="no-posts-message">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <div class="mt-2">
                        <span>Ответов пока нет</span>
                    </div>
                </div>
                @else
                @foreach($posts as $post)
                <div class="p-3 border-bottom post-item" id="post-{{ $post->id }}">
                    <!-- Информация об авторе -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            @if($post->user->avatar_url)
                            <img src="{{ $post->user->avatar_url }}" class="rounded-circle me-3" width="40" height="40"
                                alt="Avatar">
                            @else
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-person text-white"></i>
                            </div>
                            @endif
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <a href="{{ route('profile.show', $post->user->id) }}"
                                        class="text-decoration-none fw-semibold text-primary">
                                        {!! $post->user->styled_username !!}
                                    </a>
                                    @if($post->user->id === $topic->user_id)
                                    <span class="badge bg-primary">Автор</span>
                                    @endif
                                    <div class="d-none d-md-flex align-items-center gap-3">
                                        <small class="text-muted">
                                            <i class="bi bi-chat me-1"></i>{{ $post->user->posts_count ?? 0 }}
                                        </small>
                                    </div>
                                </div>
                                <div class="small text-muted">{{ $post->created_at->diffForHumans() }}</div>
                            </div>
                        </div>

                        <!-- Dropdown меню вверху справа -->
                        @if(auth()->check() && auth()->user()->canModerate() && auth()->id() !== $post->user_id)
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- Удаление ответа - только модераторы/админы -->
                                <li>
                                    <button class="dropdown-item text-danger"
                                        onclick="showDeletePostModal({{ $post->id }})">
                                        <i class="bi bi-trash me-2"></i>Удалить ответ
                                    </button>
                                </li>
                            </ul>
                        </div>
                        @endif
                        @auth
                        @if(auth()->id() === $post->user_id)<div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if(auth()->id() === $post->user_id)
                                <li>
                                    @if($post->canEdit())
                                    <a href="{{ route('posts.edit', $post->id) }}"
                                        class="dropdown-item">
                                        <i class="bi bi-pencil"></i> Редактировать
                                    </a>
                                    @endif
                                </li>
                                <li>
                                    @if($post->canDelete())
                                    <form method="POST" action="{{ route('posts.destroy', $post->id) }}"
                                        style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item"
                                            onclick="return confirm('Вы уверены, что хотите удалить этот пост?')">
                                            <i class="bi bi-trash"></i> Удалить
                                        </button>
                                    </form>
                                    @endif
                                </li>
                                @endif
                            </ul>
                        </div>
                        @endif @endauth
                    </div>

                    <!-- Рейтинг и посты на мобильных устройствах -->
                    <div class="d-md-none mb-3">
                        <div class="d-flex gap-3">
                            <small class="text-muted">
                                <i class="bi bi-star me-1"></i>{{ $post->user->rating ?? 0 }}
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-chat me-1"></i>{{ $post->user->posts_count ?? 0 }}
                            </small>
                        </div>
                    </div>

                    <!-- Ответ на сообщение -->
                    @if($post->replyToPost && $post->replyToPost->user)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-reply me-1"></i>В ответ на
                            <a href="{{ $post->replyToPost->permalink }}" class="text-decoration-none reply-link"
                                data-user="{{ $post->replyToPost->user->username }}">
                                {{ $post->replyToPost->user->username }}
                            </a>
                        </small>
                        <blockquote class="border-start border-3 ps-3 mb-0">
                            @php
                            $replyContent = \Illuminate\Support\Str::limit(strip_tags($post->replyToPost->content), 200);
                            @endphp
                            {{ $replyContent }}
                        </blockquote>
                    </div>
                    @endif

                    <!-- Цитата (если есть) -->
                    @if($post->quoted_content)
                    <div class="bg-light border-start border-3 border-secondary p-2 mb-3 rounded-end">
                        <small class="text-muted mb-1 d-block">
                            @if($post->parent)
                            <i class="bi bi-quote me-1"></i>{{ $post->parent->user->username }} писал:
                            @endif
                        </small>
                        <div class="small">
                            @php
                            $quotedContent = \Illuminate\Support\Str::limit($post->quoted_content, 200);
                            $processedQuote = \App\Helpers\MentionHelper::parseUserMentions($quotedContent);
                            $processedQuote = nl2br($processedQuote, false);
                            $markdownQuote = \Illuminate\Support\Str::markdown($processedQuote);
                            $cleanQuote = \Mews\Purifier\Facades\Purifier::clean($markdownQuote, 'forum');

                            $cleanQuote = preg_replace(
                            '/<img([^>]*?)>/i',
                                '<img$1 class="img-thumbnail rounded" style="max-width:150px;height:auto;">',
                                    $cleanQuote
                                    );
                                    @endphp
                                    {!! $cleanQuote !!}
                        </div>
                    </div>
                    @endif

                    <!-- Содержимое поста -->
                    <div class="post-content mb-3">
                        {!! \App\Helpers\ContentProcessor::processContent($post->content) !!}
                    </div>


                    @if($post->edited_text)
                    <small class="text-muted ms-2">
                        <i class="bi bi-pencil"></i>
                        {{ $post->edited_text }}
                    </small>
                    @endif

                    <!-- Прикрепленные файлы к посту -->
                    @if($post->files && $post->files->count() > 0)
                    <div class="mt-3">
                        <h6><i class="bi bi-paperclip me-2"></i>Прикреплённые файлы:</h6>

                        @php
                        $allowFileDownload = env('ALLOW_FILE_DOWNLOAD', 1);
                        @endphp
                        <div class="row">
                            @foreach($post->files as $file)
                            <div class="col-md-6 mb-3">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            @php
                                            $extension = strtolower(pathinfo($file->original_name,
                                            PATHINFO_EXTENSION));
                                            $iconClass = 'bi-file-earmark';
                                            $badgeClass = 'bg-secondary';

                                            if (in_array($extension, ['zip', 'rar', '7z'])) {
                                            $iconClass = 'bi-file-zip';
                                            $badgeClass = 'bg-warning';
                                            } elseif (in_array($extension, ['php', 'js', 'css', 'html', 'py'])) {
                                            $iconClass = 'bi-file-code';
                                            $badgeClass = 'bg-primary';
                                            } elseif (in_array($extension, ['txt', 'json', 'xml', 'sql'])) {
                                            $iconClass = 'bi-file-text';
                                            $badgeClass = 'bg-info';
                                            } elseif (in_array($extension, ['pdf'])) {
                                            $iconClass = 'bi-file-pdf';
                                            $badgeClass = 'bg-danger';
                                            } elseif (in_array($extension, ['doc', 'docx'])) {
                                            $iconClass = 'bi-file-word';
                                            $badgeClass = 'bg-success';
                                            }
                                            @endphp

                                            <i class="bi {{ $iconClass }} text-primary me-3 fs-3"></i>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <strong class="me-2">{{ $file->original_name }}</strong>
                                                    <span
                                                        class="badge {{ $badgeClass }}">{{ strtoupper($extension) }}</span>
                                                </div>
                                                <small class="text-muted">
                                                    {{ $file->formatted_size }}
                                                    @if($file->downloads_count > 0)
                                                    • <i class="bi bi-download"></i> {{ $file->downloads_count }}
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="ms-2">
                                                @if($allowFileDownload == 1 || auth()->check())
                                                <a href="{{ $file->download_url }}"
                                                    class="btn btn-sm btn-outline-primary me-1" title="Скачать файл">
                                                    <i class="bi bi-download"></i> Скачать
                                                </a>
                                                @else
                                                <button class="btn btn-sm btn-outline-secondary me-1"
                                                    onclick="alert('Для скачивания файлов необходимо зарегистрироваться')"
                                                    title="Требуется авторизация">
                                                    <i class="bi bi-lock"></i> Скачать
                                                </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <!-- Действия с постом (закреплены внизу справа) -->
                    <div class="d-flex justify-content-end gap-1">
                        @auth
        @if(!auth()->user()->isBanned())
            @if($post->user_id !== auth()->id())
                {{-- Показываем кнопку лайка только если пользователь НЕ автор поста --}}
                <button class="btn btn-outline-primary btn-sm like-post-btn"
                        data-post-id="{{ $post->id }}"
                        data-bs-toggle="popover"
                        data-bs-trigger="hover"
                        data-bs-placement="top"
                        data-bs-html="true"
                        data-like-type="post"
                        data-like-id="{{ $post->id }}">
                    <i class="bi bi-heart{{ $post->isLikedBy(auth()->id()) ? '-fill text-danger' : '' }}"></i>
                    <span class="like-count ms-1">{{ $post->likes_count }}</span>
                </button>
            @else
                {{-- Для автора показываем кнопку с просмотром, но без возможности лайкать --}}
                <button class="btn btn-outline-secondary btn-sm like-post-btn"
                        data-post-id="{{ $post->id }}"
                        data-bs-toggle="popover"
                        data-bs-trigger="hover"
                        data-bs-placement="top"
                        data-bs-html="true"
                        data-like-type="post"
                        data-like-id="{{ $post->id }}"
                        data-is-author="true"
                        title="Посмотреть, кто лайкнул">
                    <i class="bi bi-heart"></i>
                    <span class="like-count ms-1">{{ $post->likes_count }}</span>
                </button>
            @endif
        @else
            <button class="btn btn-outline-secondary btn-sm" disabled title="Ваш аккаунт ограничен">
                <i class="bi bi-heart"></i>
                <span class="like-count ms-1">{{ $post->likes_count }}</span>
            </button>
        @endif
        
        @if(!auth()->user()->isBanned())
            <button class="btn btn-outline-secondary btn-sm reply-btn" data-post-id="{{ $post->id }}"
                data-author="{{ $post->user->username }}"
                data-content="{{ Str::limit(strip_tags($post->content), 200) }}">
                <i class="bi bi-reply"></i>
                <span class="d-none d-lg-inline ms-1">Ответить</span>
            </button>
        @endif
                        @endauth
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

        <!-- Пагинация -->
        @if($posts->hasPages())
        <div class="d-flex justify-content-center mb-4">
            <nav aria-label="Page navigation">
                <ul class="pagination">

                    {{-- Предыдущая страница --}}
                    <li class="page-item {{ $posts->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $posts->previousPageUrl() }}" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>

                    {{-- Номера страниц --}}
                    @foreach($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(),
                    $posts->currentPage() + 2)) as $page => $url)
                    <li class="page-item {{ $page == $posts->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                    @endforeach

                    {{-- Следующая страница --}}
                    <li class="page-item {{ !$posts->hasMorePages() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $posts->nextPageUrl() }}" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif

        <!-- Форма ответа -->
        @auth
        @if(!auth()->user()->canPerformActions())
        <!-- Заблокированный пользователь -->
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-x me-3 text-danger" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1 text-danger">Ваш аккаунт ограничен</h6>
                            <p class="mb-0">Вы не можете отвечать в темах, создавать новые темы или ставить лайки.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('banned') }}" class="btn btn-outline-danger">
                        <i class="bi bi-info-circle me-1"></i>Подробнее
                    </a>
                </div>
            </div>
        </div>
        @elseif($topic->is_closed && !auth()->user()->isStaff())
        <!-- Закрытая тема для обычных пользователей -->
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lock me-3 text-warning" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1 text-warning">Тема закрыта</h6>
                            <p class="mb-0">Тема закрыта для ответов. Только модераторы и администраторы могут
                                отвечать в закрытых темах.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif($topic->is_locked)
        <!-- Заблокированная тема (старая проверка, оставляем для совместимости) -->
        <div class="alert alert-warning text-center">
            <i class="bi bi-lock me-2"></i>Тема заблокирована для комментирования
        </div>
        @else
        <!-- Форма ответа для авторизованных пользователей -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ответить в теме</h5>
                @if($topic->is_closed && auth()->user()->isStaff())
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-lock me-1"></i>Тема закрыта (модераторский доступ)
                </span>
                @endif
            </div>

            <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="reply-form">
                @csrf
                <input type="hidden" name="topic_id" value="{{ $topic->id }}">
                <input type="hidden" name="parent_id" id="parent_id">
                <input type="hidden" name="quoted_content" id="quoted_content">
                <input type="hidden" name="reply_to_post_id" id="reply_to_post_id">

                <div id="reply-preview" style="display: none;" class="mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-reply me-1"></i>В ответ на <span id="reply-author"></span>
                            </small>
                            <blockquote class="border-start border-3 ps-3 mb-0 small text-muted" id="reply-text"></blockquote>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="remove-reply">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="editor-container">
                        <!-- Quill Editor для ответов -->
                        <div id="quill-reply-editor" style="min-height: 150px;"></div>

                        <!-- Скрытое поле для отправки HTML -->
                        <textarea name="content" id="content" style="display: none;"></textarea>

                        <!-- Счетчик символов -->
                        <div class="char-counter" id="reply-char-count">0 символов</div>
                    </div>

                    <!-- Файлы -->
                    <input type="file" id="image-input" accept="image/*" style="display: none;">
                    <input type="file" id="files" name="files[]" multiple
                        accept=".zip,.rar,.7z,.txt,.pdf,.doc,.docx,.json,.xml" style="display: none;">

                    <!-- Предварительный просмотр файлов -->
                    <div id="file-preview" class="mt-2" style="display: none;">
                        <h6 class="small"><i class="bi bi-paperclip me-1"></i>Прикреплённые файлы:</h6>
                        <div id="file-list" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>


                <div class="mb-3 container d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="bi bi-send me-1"></i>Отправить
                    </button>
                </div>
            </form>
        </div>
        @endif
        @else
        <!-- Форма для неавторизованных пользователей -->
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <h6 class="mb-1">Присоединяйтесь к обсуждению!</h6>
                            <p class="mb-0 text-muted">Войдите или зарегистрируйтесь, чтобы оставлять комментарии,
                                ставить лайки и добавлять темы в закладки.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Войти
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i>Регистрация
                    </a>
                </div>
            </div>
        </div>
        @endauth
    </div>

    <!-- Боковая панель -->
    <div class="col-lg-3">
        <!-- Информация о теме -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Информация о теме</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 border-end mb-3">
                        <strong class="d-block">{{ $topic->views ?? 0 }}</strong>
                        <small class="text-muted">Просмотров</small>
                    </div>
                    <div class="col-6 mb-3">
                        <strong class="d-block">{{ $topic->replies_count ?? 0 }}</strong>
                        <small class="text-muted">Ответов</small>
                    </div>
                    <div class="col-6 border-end">
                        <strong class="d-block">{{ $topic->likes_count ?? 0 }}</strong>
                        <small class="text-muted">Лайков</small>
                    </div>
                    <div class="col-6">
                        <strong class="d-block">{{ $topic->participants_count ?? 1 }}</strong>
                        <small class="text-muted">Участников</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Участники -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Участники</h6>
            </div>
            <div class="list-group list-group-flush">
                @php
                // Собираем статистику по участникам
                $participantsStats = $posts->groupBy('user_id')->map(function($userPosts) {
                return [
                'user' => $userPosts->first()->user,
                'posts_count' => $userPosts->count(),
                'likes_received' => $userPosts->sum('likes_count'),
                'recent_activity' => $userPosts->max('created_at')
                ];
                });

                // Добавляем автора темы, если он не участвовал в обсуждении
                if (!$participantsStats->has($topic->user_id)) {
                $participantsStats->put($topic->user_id, [
                'user' => $topic->user,
                'posts_count' => 0,
                'likes_received' => 0,
                'recent_activity' => $topic->created_at,
                'is_author' => true
                ]);
                }

                // Сортируем по комплексной оценке активности
                $topParticipants = $participantsStats->sortByDesc(function($participant) {
                // Весовая формула: (ответы * 3) + (лайки * 2) + (активность в днях)
                $activityScore = $participant['posts_count'] * 3
                + $participant['likes_received'] * 2
                + now()->diffInDays($participant['recent_activity']);
                return $activityScore;
                })->take(10);
                @endphp

                @foreach($topParticipants as $participant)
                <div class="list-group-item d-flex align-items-center">
                    @if($participant['user']->avatar_url)
                    <img src="{{ $participant['user']->avatar_url }}" alt="Avatar" class="rounded-circle me-2"
                        width="32" height="32">
                    @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2"
                        style="width: 32px; height: 32px;">
                        <i class="bi bi-person text-white small"></i>
                    </div>
                    @endif
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <a href="{{ route('profile.show', $participant['user']) }}" class="text-decoration-none">
                                {{ $participant['user']->username }}
                            </a>
                            @if(isset($participant['is_author']) || $participant['user']->role === 'admin')
                            <span class="badge bg-primary ms-2">Автор</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@section('scripts')
@auth
<script src="{{ asset('js/quill-forum.js') }}"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автоматический скролл к посту если есть якорь в URL
    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(() => {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                target.style.backgroundColor = '#fff3cd';
                setTimeout(() => {
                    target.style.backgroundColor = '';
                }, 2000);
            }, 100);
        }
    }
});

// Лайки постов
function handlePostLike() {
    @auth
    @if(auth()->user()->isBanned())
    showToast('warning', 'Аккаунт ограничен', 'Ваш аккаунт ограничен. Вы не можете ставить лайки.');
    return;
    @endif
    @endauth

    // Не позволяем автору лайкать свой пост
    if (this.dataset.isAuthor === 'true') {
        return;
    }

    const postId = this.dataset.postId;
    const icon = this.querySelector('i');
    const countSpan = this.querySelector('.like-count');

    this.disabled = true;

    fetch(`/posts/${postId}/like`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().catch(() => {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }).then(data => {
                    if (response.status === 429) {
                        throw new Error(data.error || 'Слишком много запросов. Попробуйте позже.');
                    }
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.banned) {
                showToast('warning', 'Аккаунт ограничен', data.error ||
                    'Ваш аккаунт ограничен. Вы не можете ставить лайки.');
                return;
            }

            if (data.error) {
                showToast('error', 'Ошибка!', data.error);
                return;
            }

            if (data.success) {
                if (data.liked) {
                    showToast('success', 'Лайк добавлен!', 'Вам понравился этот пост');
                    icon.classList.add('bi-heart-fill', 'text-danger');
                    icon.classList.remove('bi-heart');
                } else {
                    showToast('info', 'Лайк убран', 'Лайк удален');
                    icon.classList.remove('bi-heart-fill', 'text-danger');
                    icon.classList.add('bi-heart');
                }

                if (countSpan) {
                    countSpan.textContent = data.likes_count || 0;
                }
            }
        })
        .catch(error => {
            console.error('Like Error:', error);
            showToast('error', 'Ошибка!', error.message || 'Произошла ошибка при обработке лайка');
        })
        .finally(() => {
            setTimeout(() => {
                this.disabled = false;
            }, 1000);
        });
}

// Привязываем обработчики к существующим постам
document.querySelectorAll('.like-post-btn').forEach(function(btn) {
    btn.addEventListener('click', handlePostLike);
});

// Лайки тем
document.querySelectorAll('.like-topic-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        // Не позволяем автору лайкать свою тему
        if (this.dataset.isAuthor === 'true') {
            return;
        }

        const topicId = this.dataset.topicId;
        const icon = this.querySelector('i');
        const countSpans = document.querySelectorAll('.like-count');

        this.disabled = true;

        fetch(`/topics/${topicId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().catch(() => {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }).then(data => {
                        if (response.status === 429) {
                            throw new Error(data.error ||
                                'Слишком много запросов. Попробуйте позже.');
                        }
                        throw new Error(data.error ||
                            `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.banned) {
                    showToast('warning', 'Аккаунт ограничен', data.error ||
                        'Ваш аккаунт ограничен. Вы не можете ставить лайки.');
                    return;
                }

                if (data.error) {
                    showToast('error', 'Ошибка!', data.error);
                    return;
                }

                if (data.success) {
                    if (data.liked) {
                        showToast('success', 'Тема понравилась!', 'Лайк добавлен к теме');
                        icon.classList.add('bi-heart-fill', 'text-danger');
                        icon.classList.remove('bi-heart');
                    } else {
                        showToast('info', 'Лайк убран', 'Лайк удален с темы');
                        icon.classList.remove('bi-heart-fill', 'text-danger');
                        icon.classList.add('bi-heart');
                    }

                    countSpans.forEach(span => {
                        span.textContent = data.likes_count || 0;
                    });
                }
            })
            .catch(error => {
                console.error('Topic Like Error:', error);
                showToast('error', 'Ошибка!', error.message ||
                    'Произошла ошибка при обработке лайка темы');
            })
            .finally(() => {
                setTimeout(() => {
                    this.disabled = false;
                }, 1000);
            });
    });
});

// Закладки
document.querySelectorAll('.bookmark-topic-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const topicId = this.dataset.topicId;
        const icon = this.querySelector('i');

        fetch(`/topics/${topicId}/bookmark`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.bookmarked) {
                    showToast('success', 'Закладка добавлена!', 'Тема сохранена в закладки');
                    icon.classList.add('bi-bookmark-fill');
                    icon.classList.remove('bi-bookmark');
                    this.innerHTML = '<i class="bi bi-bookmark-fill me-2"></i>Убрать закладку';
                } else {
                    showToast('info', 'Закладка удалена', 'Тема убрана из закладок');
                    icon.classList.remove('bi-bookmark-fill');
                    icon.classList.add('bi-bookmark');
                    this.innerHTML = '<i class="bi bi-bookmark me-2"></i>В закладки';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Ошибка!', 'Произошла ошибка при работе с закладками');
            });
    });
});

// Простые ответы (без сложного редактора)
document.querySelectorAll('.reply-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const author = this.dataset.author;
        const textarea = document.getElementById('content');

        if (textarea) {
            textarea.value = `@${author} `;
            textarea.scrollIntoView({
                behavior: 'smooth'
            });
            textarea.focus();
        }
    });
});

// AJAX обработка формы ответа
const replyForm = document.getElementById('reply-form');
if (replyForm) {
    replyForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Предотвращаем стандартную отправку формы

        const contentTextarea = this.querySelector('#content');
        const submitBtn = document.getElementById('submit-btn');

        // Валидация - проверяем текст без HTML тегов
        const textContent = contentTextarea ? contentTextarea.value.replace(/<[^>]*>/g, '').trim() : '';

        if (!textContent || textContent.length < 3) {
            showToast('warning', 'Слишком короткое сообщение', 'Сообщение должно содержать минимум 3 символа текста');
            return false;
        }

        // Блокируем кнопку отправки
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Отправка...';

        // Собираем данные формы
        const formData = new FormData(this);

        // Отправляем AJAX-запрос
        fetch('{{ route("posts.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw { status: response.status, data: data };
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Показываем уведомление об успехе
                showToast('success', 'Успешно!', data.message || 'Ответ опубликован!');

                // Вставляем новый пост в список
                insertNewPost(data.post);

                // Обновляем счётчик ответов
                updateRepliesCount(data.topic.replies_count);

                // Очищаем форму
                resetReplyForm();

                // Скроллим к новому посту
                setTimeout(() => {
                    const newPost = document.getElementById('post-' + data.post.id);
                    if (newPost) {
                        newPost.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        newPost.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            newPost.style.backgroundColor = '';
                        }, 2000);
                    }
                }, 100);
            }
        })
        .catch(error => {
            console.error('Error details:', error);

            if (error.status === 422) {
                // Ошибки валидации
                const errors = error.data?.errors || {};
                let errorMessage = error.data?.error || error.data?.message || 'Проверьте правильность заполнения полей';

                if (errors.content) {
                    errorMessage = errors.content[0];
                }

                showToast('error', 'Ошибка валидации', errorMessage);
            } else if (error.status === 403) {
                // Доступ запрещён (бан или закрытая тема)
                showToast('error', 'Доступ запрещён', error.data?.error || error.data?.message || 'У вас нет прав для отправки сообщений');
            } else if (error.status === 429) {
                // Rate limiting
                showToast('warning', 'Слишком быстро', error.data?.error || error.data?.message || 'Подождите немного перед отправкой следующего сообщения');
            } else if (error.status === 409) {
                // Duplicate submission
                showToast('warning', 'Подождите', error.data?.message || 'Сообщение уже отправляется');
            } else {
                // Общая ошибка
                showToast('error', 'Ошибка!', error.data?.message || error.data?.error || 'Произошла ошибка при отправке сообщения');
            }
        })
        .finally(() => {
            // Разблокируем кнопку
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send me-1"></i>Отправить';
        });
    });
}
document.querySelectorAll('.reply-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const author = this.dataset.author;
        const content = this.dataset.content;
        replyToPost(postId, author, content);
    });
});

// Обработчики для удаления ответов
document.getElementById('remove-reply')?.addEventListener('click', function() {
    document.getElementById('reply-preview').style.display = 'none';
    document.getElementById('reply_to_post_id').value = '';
});

// Функция для ответа на пост
function replyToPost(postId, author, content) {
    const textarea = document.getElementById('content');
    const replyPreview = document.getElementById('reply-preview');
    const replyAuthor = document.getElementById('reply-author');
    const replyText = document.getElementById('reply-text');
    const replyToPostIdInput = document.getElementById('reply_to_post_id');

    if (replyPreview && textarea) {
        replyAuthor.textContent = `@${author}`;
        // Показываем превью контента (максимум 200 символов)
        const contentPreview = content ? content.substring(0, 200) : `Ответ пользователю ${author}`;
        replyText.textContent = contentPreview;
        replyToPostIdInput.value = postId;
        replyPreview.style.display = 'block';

        textarea.value = `@${author} `;
        textarea.scrollIntoView({
            behavior: 'smooth'
        });
        textarea.focus();
    }
}

// Функция для вставки нового поста в DOM
function insertNewPost(post) {
    const postsList = document.getElementById('posts-list');
    const noPostsMessage = document.getElementById('no-posts-message');

    // Убираем сообщение "Ответов пока нет" если оно есть
    if (noPostsMessage) {
        noPostsMessage.remove();
    }

    // Создаём HTML нового поста
    const postHtml = createPostHtml(post);

    // Вставляем пост в конец списка
    postsList.insertAdjacentHTML('beforeend', postHtml);

    // Привязываем обработчики событий к новому посту
    attachPostEventListeners(post.id);
}

// Функция для создания HTML поста
function createPostHtml(post) {
    const topicAuthorId = {{ $topic->user_id }};
    const currentUserId = {{ auth()->id() }};
    const isModerator = {{ auth()->user()->canModerate() ? 'true' : 'false' }};
    const isBanned = {{ auth()->user()->isBanned() ? 'true' : 'false' }};

    // Создаём HTML для аватара
    let avatarHtml = '';
    if (post.user.avatar_url) {
        avatarHtml = `<img src="${escapeHtml(post.user.avatar_url)}" class="rounded-circle me-3" width="40" height="40" alt="Avatar">`;
    } else {
        avatarHtml = `
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <i class="bi bi-person text-white"></i>
            </div>`;
    }

    // Создаём HTML для бейджа "Автор"
    const authorBadge = post.user.id === topicAuthorId ? '<span class="badge bg-primary">Автор</span>' : '';

    // Создаём HTML для прикреплённых файлов
    let filesHtml = '';
    if (post.files && post.files.length > 0) {
        filesHtml = '<div class="mt-3"><h6><i class="bi bi-paperclip me-2"></i>Прикреплённые файлы:</h6><div class="row">';

        post.files.forEach(file => {
            const extension = file.original_name.split('.').pop().toLowerCase();
            let iconClass = 'bi-file-earmark';
            let badgeClass = 'bg-secondary';

            if (['zip', 'rar', '7z'].includes(extension)) {
                iconClass = 'bi-file-zip';
                badgeClass = 'bg-warning';
            } else if (['php', 'js', 'css', 'html', 'py'].includes(extension)) {
                iconClass = 'bi-file-code';
                badgeClass = 'bg-primary';
            } else if (['txt', 'json', 'xml', 'sql'].includes(extension)) {
                iconClass = 'bi-file-text';
                badgeClass = 'bg-info';
            } else if (extension === 'pdf') {
                iconClass = 'bi-file-pdf';
                badgeClass = 'bg-danger';
            } else if (['doc', 'docx'].includes(extension)) {
                iconClass = 'bi-file-word';
                badgeClass = 'bg-success';
            }

            filesHtml += `
                <div class="col-md-6 mb-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <i class="bi ${iconClass} text-primary me-3 fs-3"></i>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="me-2">${escapeHtml(file.original_name)}</strong>
                                        <span class="badge ${badgeClass}">${extension.toUpperCase()}</span>
                                    </div>
                                    <small class="text-muted">${file.formatted_size}</small>
                                </div>
                                <div class="ms-2">
                                    <a href="${file.download_url}" class="btn btn-sm btn-outline-primary me-1" title="Скачать файл">
                                        <i class="bi bi-download"></i> Скачать
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        filesHtml += '</div></div>';
    }

    // Создаём HTML для ответа на сообщение
    let replyToHtml = '';
    if (post.reply_to_post && post.reply_to_post.user) {
        const replyContent = post.reply_to_post.content.replace(/<[^>]*>/g, '').substring(0, 200);
        replyToHtml = `
            <div class="mb-3">
                <small class="text-muted d-block mb-2">
                    <i class="bi bi-reply me-1"></i>В ответ на
                    <a href="${post.reply_to_post.permalink}" class="text-decoration-none reply-link" data-user="${escapeHtml(post.reply_to_post.user.username)}">
                        ${escapeHtml(post.reply_to_post.user.username)}
                    </a>
                </small>
                <blockquote class="border-start border-3 ps-3 mb-0">
                    ${replyContent}${post.reply_to_post.content.length > 200 ? '...' : ''}
                </blockquote>
            </div>`;
    }

    // Кнопки лайков и ответа
    let actionButtonsHtml = '';
    if (!isBanned) {
        if (post.user.id !== currentUserId) {
            actionButtonsHtml += `
                <button class="btn btn-outline-primary btn-sm like-post-btn"
                        data-post-id="${post.id}"
                        data-bs-toggle="popover"
                        data-bs-trigger="hover"
                        data-bs-placement="top"
                        data-bs-html="true"
                        data-like-type="post"
                        data-like-id="${post.id}">
                    <i class="bi bi-heart${post.is_liked ? '-fill text-danger' : ''}"></i>
                    <span class="like-count ms-1">${post.likes_count}</span>
                </button>`;
        } else {
            actionButtonsHtml += `
                <button class="btn btn-outline-secondary btn-sm" disabled title="Нельзя лайкать собственный пост">
                    <i class="bi bi-heart"></i>
                    <span class="like-count ms-1">${post.likes_count}</span>
                </button>`;
        }

        const postContentPreview = post.content.replace(/<[^>]*>/g, '').substring(0, 200);
        actionButtonsHtml += `
            <button class="btn btn-outline-secondary btn-sm reply-btn"
                data-post-id="${post.id}"
                data-author="${escapeHtml(post.user.username)}"
                data-content="${escapeHtml(postContentPreview)}">
                <i class="bi bi-reply"></i>
                <span class="d-none d-lg-inline ms-1">Ответить</span>
            </button>`;
    }

    // Dropdown меню для автора или модератора
    let dropdownHtml = '';
    if (post.user.id === currentUserId) {
        dropdownHtml = `
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a href="/posts/${post.id}/edit" class="dropdown-item">
                            <i class="bi bi-pencil"></i> Редактировать
                        </a>
                    </li>
                </ul>
            </div>`;
    } else if (isModerator && post.user.id !== currentUserId) {
        dropdownHtml = `
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item text-danger" onclick="showDeletePostModal(${post.id})">
                            <i class="bi bi-trash me-2"></i>Удалить ответ
                        </button>
                    </li>
                </ul>
            </div>`;
    }

    // Собираем всё вместе
    return `
        <div class="p-3 border-bottom post-item" id="post-${post.id}">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    ${avatarHtml}
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="/profile/${post.user.id}" class="text-decoration-none fw-semibold text-primary">
                                ${escapeHtml(post.user.username)}
                            </a>
                            ${authorBadge}
                            <div class="d-none d-md-flex align-items-center gap-3">
                                <small class="text-muted">
                                    <i class="bi bi-chat me-1"></i>${post.user.posts_count || 0}
                                </small>
                            </div>
                        </div>
                        <div class="small text-muted">${post.created_at}</div>
                    </div>
                </div>
                ${dropdownHtml}
            </div>

            <div class="d-md-none mb-3">
                <div class="d-flex gap-3">
                    <small class="text-muted">
                        <i class="bi bi-star me-1"></i>${post.user.rating || 0}
                    </small>
                    <small class="text-muted">
                        <i class="bi bi-chat me-1"></i>${post.user.posts_count || 0}
                    </small>
                </div>
            </div>

            ${replyToHtml}

            <div class="post-content mb-3">
                ${post.content}
            </div>

            ${filesHtml}

            <div class="d-flex justify-content-end gap-1">
                ${actionButtonsHtml}
            </div>
        </div>`;
}

// Функция для обновления счётчика ответов
function updateRepliesCount(count) {
    const counters = document.querySelectorAll('.replies-count');
    counters.forEach(counter => {
        counter.textContent = count;
    });
}

// Функция для сброса формы
function resetReplyForm() {
    // Сбрасываем Quill Editor
    if (window.replyQuill) {
        window.replyQuill.setContents([]);
    }

    // Очищаем скрытое поле content
    const contentField = document.getElementById('content');
    if (contentField) {
        contentField.value = '';
    }

    // Сбрасываем скрытые поля
    document.getElementById('parent_id').value = '';
    document.getElementById('quoted_content').value = '';
    document.getElementById('reply_to_post_id').value = '';

    // Скрываем preview ответа
    const replyPreview = document.getElementById('reply-preview');
    if (replyPreview) {
        replyPreview.style.display = 'none';
    }

    // Очищаем файлы
    const filesInput = document.getElementById('files');
    if (filesInput) {
        filesInput.value = '';
    }

    const filePreview = document.getElementById('file-preview');
    if (filePreview) {
        filePreview.style.display = 'none';
    }

    const fileList = document.getElementById('file-list');
    if (fileList) {
        fileList.innerHTML = '';
    }

    // Очищаем черновик из LocalStorage
    clearReplyDraft();
}

// ========== СИСТЕМА ЧЕРНОВИКОВ ==========

// Ключ для хранения черновика конкретной темы
const DRAFT_KEY = 'forum_draft_topic_{{ $topic->id }}';
const DRAFT_EXPIRY_DAYS = 30;

// Сохранение черновика
function saveDraft() {
    // Получаем содержимое напрямую из Quill Editor
    if (!window.replyQuill) return;

    const quillContent = window.replyQuill.root.innerHTML;
    const textContent = window.replyQuill.getText().trim();

    // Если контента нет или он пустой - удаляем черновик
    if (!textContent || textContent.length === 0 || quillContent.trim() === '<p><br></p>' || quillContent.trim() === '') {
        clearReplyDraft();
        return;
    }

    // Сохраняем как HTML, так и Delta (для сохранения изображений)
    const draft = {
        content: quillContent,
        delta: window.replyQuill.getContents(), // Сохраняем Delta с изображениями
        timestamp: Date.now(),
        expiresAt: Date.now() + (DRAFT_EXPIRY_DAYS * 24 * 60 * 60 * 1000)
    };

    try {
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    } catch (e) {
        console.error('Ошибка сохранения черновика:', e);
    }
}

// Загрузка черновика
function loadDraft() {
    try {
        const draftData = localStorage.getItem(DRAFT_KEY);
        if (!draftData) return null;

        const draft = JSON.parse(draftData);

        // Проверяем срок действия
        if (Date.now() > draft.expiresAt) {
            localStorage.removeItem(DRAFT_KEY);
            return null;
        }

        return draft;
    } catch (e) {
        console.error('Ошибка загрузки черновика:', e);
        return null;
    }
}

// Очистка черновика
function clearReplyDraft() {
    try {
        localStorage.removeItem(DRAFT_KEY);
    } catch (e) {
        console.error('Ошибка очистки черновика:', e);
    }
}

// Восстановление черновика при загрузке страницы
function restoreDraft() {
    const draft = loadDraft();
    if (!draft) return;

    // Автоматически восстанавливаем без уведомления
    const contentField = document.getElementById('content');
    if (contentField) {
        contentField.value = draft.content;
    }

    // Восстанавливаем в Quill Editor
    // Ждём инициализации Quill
    const waitForQuill = setInterval(() => {
        if (window.replyQuill) {
            clearInterval(waitForQuill);

            // Если есть сохраненная Delta с изображениями - используем её
            if (draft.delta) {
                try {
                    window.replyQuill.setContents(draft.delta, 'silent');
                } catch (e) {
                    console.error('Ошибка восстановления Delta:', e);
                    // Fallback на HTML
                    const deltaFromHtml = window.replyQuill.clipboard.convert(draft.content);
                    window.replyQuill.setContents(deltaFromHtml, 'silent');
                }
            } else {
                // Старый формат - используем HTML
                const deltaFromHtml = window.replyQuill.clipboard.convert(draft.content);
                window.replyQuill.setContents(deltaFromHtml, 'silent');
            }
        }
    }, 100);

    // Останавливаем ожидание через 5 секунд
    setTimeout(() => clearInterval(waitForQuill), 5000);
}

// Автосохранение каждые 10 секунд
let draftSaveTimer;
function startDraftAutosave() {
    // Очищаем предыдущий таймер если есть
    if (draftSaveTimer) {
        clearInterval(draftSaveTimer);
    }

    // Запускаем автосохранение
    draftSaveTimer = setInterval(() => {
        if (window.replyQuill) {
            const textContent = window.replyQuill.getText().trim();
            // Сохраняем только если есть реальный контент (больше 3 символов)
            if (textContent && textContent.length > 3) {
                saveDraft();
            } else {
                // Если контента нет - удаляем черновик
                clearReplyDraft();
            }
        }
    }, 10000); // Каждые 10 секунд
}

// Инициализация системы черновиков
document.addEventListener('DOMContentLoaded', function() {
    // Восстанавливаем черновик при загрузке
    setTimeout(() => {
        restoreDraft();
    }, 500);

    // Запускаем автосохранение
    startDraftAutosave();

    // Сохраняем при изменении содержимого в Quill
    const waitForQuillInit = setInterval(() => {
        if (window.replyQuill) {
            clearInterval(waitForQuillInit);

            // Отслеживаем изменения в Quill Editor
            window.replyQuill.on('text-change', function() {
                clearTimeout(window.draftSaveTimeout);
                window.draftSaveTimeout = setTimeout(saveDraft, 2000);
            });
        }
    }, 100);

    // Останавливаем ожидание через 10 секунд
    setTimeout(() => clearInterval(waitForQuillInit), 10000);

    // Очистка черновиков старше 30 дней во всех темах
    cleanExpiredDrafts();
});

// Очистка устаревших черновиков
function cleanExpiredDrafts() {
    try {
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith('forum_draft_topic_')) {
                const draftData = localStorage.getItem(key);
                if (draftData) {
                    try {
                        const draft = JSON.parse(draftData);
                        if (Date.now() > draft.expiresAt) {
                            keysToRemove.push(key);
                        }
                    } catch (e) {
                        keysToRemove.push(key);
                    }
                }
            }
        }

        keysToRemove.forEach(key => localStorage.removeItem(key));
    } catch (e) {
        console.error('Ошибка очистки устаревших черновиков:', e);
    }
}

// Функция для привязки обработчиков к новому посту
function attachPostEventListeners(postId) {
    const newPost = document.getElementById('post-' + postId);
    if (!newPost) return;

    // Кнопка лайка
    const likeBtn = newPost.querySelector('.like-post-btn');
    if (likeBtn && !likeBtn.hasAttribute('data-like-initialized')) {
        likeBtn.setAttribute('data-like-initialized', 'true');
        likeBtn.addEventListener('click', handlePostLike);

        // Инициализируем popover для новой кнопки лайка
        const likeCount = likeBtn.querySelector('.like-count');
        const count = parseInt(likeCount?.textContent || 0);

        if (count > 0 && likeCount) {
            likeBtn.classList.add('popover-initialized');

            let popover = null;
            let showTimeout = null;
            let hideTimeout = null;
            let contentLoaded = false;
            let cachedContent = '';

            // Загружаем контент заранее ОДИН раз
            loadLikesPopoverContent(likeBtn).then(content => {
                cachedContent = content;
                contentLoaded = true;
            });

            likeBtn.addEventListener('mouseenter', function() {
                clearTimeout(hideTimeout);

                showTimeout = setTimeout(() => {
                    // Создаем popover только если контент загружен
                    if (contentLoaded && !popover) {
                        popover = new bootstrap.Popover(likeBtn, {
                            html: true,
                            trigger: 'manual',
                            placement: 'top',
                            customClass: 'likes-preview-popover',
                            content: cachedContent,
                            sanitize: false
                        });
                    }

                    if (popover) {
                        popover.show();

                        // Добавляем обработчики к popover
                        setTimeout(() => {
                            const popoverEl = document.querySelector('.popover.likes-preview-popover:not([data-events-set])');
                            if (popoverEl) {
                                popoverEl.dataset.eventsSet = 'true';

                                popoverEl.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
                                popoverEl.addEventListener('mouseleave', () => {
                                    hideTimeout = setTimeout(() => popover && popover.hide(), 300);
                                });
                            }
                        }, 50);
                    }
                }, 500);
            });

            likeBtn.addEventListener('mouseleave', function() {
                clearTimeout(showTimeout);
                hideTimeout = setTimeout(() => {
                    if (popover) popover.hide();
                }, 300);
            });

            likeCount.style.cursor = 'pointer';
            likeCount.title = 'Нажмите, чтобы посмотреть всех кто поставил лайк';

            if (!likeCount.hasAttribute('data-click-initialized')) {
                likeCount.setAttribute('data-click-initialized', 'true');
                likeCount.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();

                    clearTimeout(showTimeout);
                    clearTimeout(hideTimeout);
                    if (popover) popover.hide();

                    const type = likeBtn.dataset.likeType;
                    const id = likeBtn.dataset.likeId;
                    openLikesModal(type, id);
                });
            }
        }
    }

    // Кнопка ответа
    const replyBtn = newPost.querySelector('.reply-btn');
    if (replyBtn) {
        replyBtn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const author = this.dataset.author;
            const content = this.dataset.content;
            replyToPost(postId, author, content);
        });
    }
}
</script>
@endauth
@endsection

<!-- Полноэкранное модальное окно для галереи -->
@if($topic->galleryImages && $topic->galleryImages->count() > 0)
<div class="modal fade" id="galleryModal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 bg-dark bg-opacity-75">
                <h5 class="modal-title text-white">
                    <i class="bi bi-images me-2"></i>Галерея изображений
                    <span class="badge bg-secondary ms-2" id="galleryCounter">1 / {{ $topic->galleryImages->count() }}</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-dark">
                <!-- Карусель галереи в полноэкранном режиме -->
                <div id="fullscreenGalleryCarousel" class="carousel slide h-100" data-bs-ride="false" data-bs-interval="false">
                    <div class="carousel-inner h-100">
                        @foreach($topic->galleryImages as $index => $image)
                        <div class="carousel-item h-100 {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                            <div class="d-flex align-items-center justify-content-center h-100 p-4">
                                <img src="{{ $image->image_url }}"
                                    class="d-block img-fluid"
                                    style="max-height: 90vh; max-width: 100%; object-fit: contain;"
                                    alt="{{ $image->original_name }}">
                            </div>
                            @if($image->description)
                            <div class="carousel-caption">
                                <div class="bg-dark bg-opacity-75 rounded p-3">
                                    <p class="mb-0">{{ $image->description }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Навигация -->
                    @if($topic->galleryImages->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#fullscreenGalleryCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" style="filter: drop-shadow(0 0 10px rgba(0,0,0,0.8));" aria-hidden="true"></span>
                        <span class="visually-hidden">Предыдущий</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#fullscreenGalleryCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" style="filter: drop-shadow(0 0 10px rgba(0,0,0,0.8));" aria-hidden="true"></span>
                        <span class="visually-hidden">Следующий</span>
                    </button>
                    @endif
                </div>
            </div>
            <!-- Миниатюры в нижней части -->
            @if($topic->galleryImages->count() > 1)
            <div class="modal-footer border-0 bg-dark bg-opacity-75 justify-content-center" style="overflow-x: auto;">
                <div class="d-flex gap-2 py-2">
                    @foreach($topic->galleryImages as $index => $image)
                    <img src="{{ $image->thumbnail_url }}"
                        class="rounded border gallery-thumb-modal {{ $index === 0 ? 'border-primary border-3' : 'border-secondary' }}"
                        style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; opacity: {{ $index === 0 ? '1' : '0.6' }};"
                        onclick="goToSlideModal({{ $index }})"
                        data-thumb-index="{{ $index }}"
                        alt="Миниатюра {{ $index + 1 }}">
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
// Открытие полноэкранной галереи
function openGalleryModal(slideIndex) {
    const galleryModal = new bootstrap.Modal(document.getElementById('galleryModal'));
    const carousel = document.getElementById('fullscreenGalleryCarousel');
    const bsCarousel = new bootstrap.Carousel(carousel);

    // Переходим к нужному слайду
    bsCarousel.to(slideIndex);

    // Открываем модальное окно
    galleryModal.show();

    // Обновляем счетчик и миниатюры
    updateGalleryUI(slideIndex);
}

// Переход к слайду в модальном окне
function goToSlideModal(slideIndex) {
    const carousel = document.getElementById('fullscreenGalleryCarousel');
    const bsCarousel = bootstrap.Carousel.getInstance(carousel) || new bootstrap.Carousel(carousel);
    bsCarousel.to(slideIndex);
    updateGalleryUI(slideIndex);
}

// Обновление UI галереи (счетчик и подсветка миниатюр)
function updateGalleryUI(currentIndex) {
    // Обновляем счетчик
    const counter = document.getElementById('galleryCounter');
    if (counter) {
        const total = {{ $topic->galleryImages->count() }};
        counter.textContent = `${currentIndex + 1} / ${total}`;
    }

    // Обновляем подсветку миниатюр
    document.querySelectorAll('.gallery-thumb-modal').forEach((thumb, index) => {
        if (index === currentIndex) {
            thumb.classList.remove('border-secondary');
            thumb.classList.add('border-primary', 'border-3');
            thumb.style.opacity = '1';
        } else {
            thumb.classList.remove('border-primary', 'border-3');
            thumb.classList.add('border-secondary');
            thumb.style.opacity = '0.6';
        }
    });
}

// Обработчик события смены слайда
document.addEventListener('DOMContentLoaded', function() {
    const fullscreenCarousel = document.getElementById('fullscreenGalleryCarousel');
    if (fullscreenCarousel) {
        fullscreenCarousel.addEventListener('slid.bs.carousel', function(event) {
            updateGalleryUI(event.to);
        });
    }

    // Обработка клавиш стрелок для навигации
    document.getElementById('galleryModal')?.addEventListener('shown.bs.modal', function() {
        document.addEventListener('keydown', galleryKeyHandler);
    });

    document.getElementById('galleryModal')?.addEventListener('hidden.bs.modal', function() {
        document.removeEventListener('keydown', galleryKeyHandler);
    });
});

function galleryKeyHandler(e) {
    const carousel = document.getElementById('fullscreenGalleryCarousel');
    const bsCarousel = bootstrap.Carousel.getInstance(carousel);

    if (!bsCarousel) return;

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        bsCarousel.prev();
    } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        bsCarousel.next();
    }
}

// Переход к слайду в обычной карусели (не модальной)
function goToSlide(slideIndex) {
    const carousel = document.getElementById('topicGalleryCarousel');
    const bsCarousel = bootstrap.Carousel.getInstance(carousel) || new bootstrap.Carousel(carousel);
    bsCarousel.to(slideIndex);

    // Обновляем подсветку миниатюр обычной карусели
    document.querySelectorAll('.gallery-thumbnail').forEach((thumb, index) => {
        if (index === slideIndex) {
            thumb.classList.add('border-primary');
            thumb.classList.remove('border-secondary');
        } else {
            thumb.classList.remove('border-primary');
            thumb.classList.add('border-secondary');
        }
    });
}
</script>
@endif

<!-- Bootstrap Modal для изображений -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="imageModalLabel">Просмотр изображения</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Закрыть"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="modalImage" src="" alt="Увеличенное изображение" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript для Bootstrap модального окна изображений
// JavaScript для Bootstrap модального окна изображений
document.addEventListener('DOMContentLoaded', function() {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    if (imageModal) {
        // Убираем ненужные обработчики show/shown
        // и добавляем правильную обработку фокуса

        imageModal.addEventListener('hidden.bs.modal', function() {
            // Убеждаемся, что фокус возвращается на body
            document.body.focus();

            // Очищаем изображение
            if (modalImage) {
                modalImage.src = '';
            }

            // Удаляем backdrop если остался (решение исходной проблемы)
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });

            // Восстанавливаем стили body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = 'auto';
            document.body.style.paddingRight = '0';
        });

        // Обработчик перед показом модального окна
        imageModal.addEventListener('show.bs.modal', function() {
            // Убираем aria-hidden перед показом
            this.removeAttribute('aria-hidden');
        });

        // Обработчик после показа модального окна
        imageModal.addEventListener('shown.bs.modal', function() {
            // Устанавливаем фокус на кнопку закрытия
            const closeButton = this.querySelector('.btn-close');
            if (closeButton) {
                closeButton.focus();
            }
        });

        // Обработчик перед закрытием модального окна
        imageModal.addEventListener('hide.bs.modal', function() {
            // Если элемент внутри модального окна имеет фокус, убираем его
            if (this.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    }

    // Универсальный обработчик для всех кликабельных изображений
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('clickable-image') ||
            (e.target.tagName === 'IMG' && e.target.hasAttribute('data-original'))) {

            e.preventDefault();
            e.stopPropagation();

            const originalUrl = e.target.getAttribute('data-original');
            if (originalUrl && modalImage && imageModal) {
                modalImage.src = originalUrl;
                modalImage.alt = e.target.alt || 'Увеличенное изображение';

                const modal = new bootstrap.Modal(imageModal, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });

                modal.show();
            }
        }
    });

    // Обработка клавиши Escape для модального окна
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && imageModal && imageModal.classList.contains('show')) {
            const modal = bootstrap.Modal.getInstance(imageModal);
            if (modal) {
                modal.hide();
            }
        }
    });
});
</script>

@if(auth()->check() && auth()->user()->canModerate())
<!-- Модальное окно перемещения темы -->
<div class="modal fade" id="moveTopicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="moveTopicForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-right-square me-2"></i>Переместить тему
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Переместить тему "<strong id="moveTopicTitle"></strong>" в другую категорию:</p>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Новая категория <span
                                class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">Выберите категорию</option>
                            @foreach(\App\Models\Category::all() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Поле для причины перемещения (необязательное) -->
                    <div class="mb-3">
                        <label for="move_reason" class="form-label">Причина перемещения <span
                                class="text-muted">(необязательно)</span></label>
                        <textarea name="reason" id="move_reason" class="form-control" rows="3"
                            placeholder="Укажите причину перемещения темы (автор получит уведомление)"></textarea>
                        <div class="form-text">Если причина указана, автор темы получит уведомление с объяснением.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-right me-1"></i>Переместить тему
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Модальное окно удаления поста -->
<div class="modal fade" id="deletePostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deletePostForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Удаление поста
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Внимание!</strong> Это действие нельзя отменить. Пост будет удален навсегда.
                    </div>

                    <p>Вы собираетесь удалить этот пост.</p>

                    <!-- Галочка для удаления без уведомления -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="post_no_notification" name="no_notification"
                            onchange="togglePostReasonField()">
                        <label class="form-check-label" for="post_no_notification">
                            Удалить без уведомления автора
                        </label>
                        <div class="form-text">Если отмечено, автор поста не получит уведомление об удалении</div>
                    </div>

                    <!-- Поле для причины удаления -->
                    <div class="mb-3" id="postReasonField">
                        <label for="post_reason" class="form-label">Причина удаления</label>
                        <textarea name="reason" id="post_reason" class="form-control" rows="3"
                            placeholder="Укажите причину удаления поста (автор получит уведомление)"></textarea>
                        <div class="form-text">Если причина указана, автор поста получит уведомление с объяснением.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить пост
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

<!-- Модальное окно для просмотра лайков -->
<div class="modal fade" id="likesModal" tabindex="-1" aria-labelledby="likesModalLabel">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="likesModalLabel">
                    <i class="bi bi-heart-fill text-danger me-2"></i>
                    Понравилось (<span id="likesTotalCount">0</span>)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Поиск -->
                <div class="mb-3">
                    <input type="text" class="form-control" id="likesSearchInput"
                           placeholder="Поиск по никнейму...">
                </div>

                <!-- Список пользователей -->
                <div id="likesList"></div>

                <!-- Индикатор загрузки -->
                <div id="likesLoading" class="text-center py-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>

                <!-- Сообщение о пустом результате -->
                <div id="likesEmpty" class="text-center text-muted py-4" style="display: none;">
                    <i class="bi bi-search fs-1"></i>
                    <p class="mt-2">Пользователей не найдено</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Глобальные переменные для управления лайками
let likesCurrentPage = 1;
let likesHasMore = true;
let likesLoading = false;
let likesSearchTimeout = null;
let likesCurrentType = null;
let likesCurrentId = null;

// Функция для загрузки контента popover
function loadLikesPopoverContent(button) {
    const type = button.dataset.likeType;
    const id = button.dataset.likeId;
    const likeCount = parseInt(button.querySelector('.like-count')?.textContent || 0);

    if (likeCount === 0) {
        return Promise.resolve('<div class="text-muted small p-2">Пока никто не поставил лайк</div>');
    }

    // Загружаем данные для preview
    return fetch(`{{ route('api.likes.preview') }}?type=${type}&id=${id}`)
        .then(response => response.json())
        .then(data => generateLikesPreviewHTML(data))
        .catch(error => {
            console.error('Error loading likes preview:', error);
            return '<div class="text-danger small p-2">Ошибка загрузки</div>';
        });
}

// Генерация HTML для preview
function generateLikesPreviewHTML(data) {
    if (!data.likes || data.likes.length === 0) {
        return '<div class="text-muted small">Пока никто не поставил лайк</div>';
    }

    let html = '<div class="likes-preview" style="max-width: 250px;">';

    data.likes.forEach(like => {
        html += `
            <div class="d-flex align-items-center mb-2">
                <img src="${like.avatar_url}" class="rounded-circle me-2"
                     width="32" height="32" style="object-fit: cover;">
                <div class="flex-grow-1">
                    <div class="fw-semibold small">${escapeHtml(like.username)}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">${like.created_at}</div>
                </div>
            </div>`;
    });

    if (data.hasMore) {
        html += `<div class="text-center mt-2">
            <small class="text-primary">Ещё ${data.total - 5}...</small>
        </div>`;
    }

    html += '</div>';
    return html;
}

// Открыть полное модальное окно со списком лайков
function openLikesModal(type, id) {
    likesCurrentType = type;
    likesCurrentId = id;
    likesCurrentPage = 1;
    likesHasMore = true;
    likesLoading = false;

    // Очищаем предыдущие данные
    document.getElementById('likesList').innerHTML = '';
    document.getElementById('likesSearchInput').value = '';
    document.getElementById('likesEmpty').style.display = 'none';
    document.getElementById('likesLoading').style.display = 'none';

    // Открываем модальное окно
    const modal = new bootstrap.Modal(document.getElementById('likesModal'));
    modal.show();

    // Загружаем первую страницу с пустым поиском
    loadLikesPage('');
}

// Загрузка страницы лайков
function loadLikesPage(search = '') {
    if (likesLoading || !likesHasMore) return;

    likesLoading = true;
    document.getElementById('likesLoading').style.display = 'block';
    document.getElementById('likesEmpty').style.display = 'none';

    const url = `{{ route('api.likes.list') }}?type=${likesCurrentType}&id=${likesCurrentId}&page=${likesCurrentPage}&search=${encodeURIComponent(search)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            document.getElementById('likesTotalCount').textContent = data.pagination.total;

            if (data.likes.length === 0 && likesCurrentPage === 1) {
                document.getElementById('likesEmpty').style.display = 'block';
                document.getElementById('likesList').innerHTML = '';
            } else {
                const listHtml = data.likes.map(like => `
                    <a href="${like.profile_url}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-2 rounded hover-bg-light mb-2"
                             style="transition: background-color 0.2s;">
                            <img src="${like.avatar_url}" class="rounded-circle me-3"
                                 width="48" height="48" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">${escapeHtml(like.username)}</div>
                                <small class="text-muted">${like.created_at}</small>
                            </div>
                            <i class="bi bi-heart-fill text-danger"></i>
                        </div>
                    </a>
                `).join('');

                if (likesCurrentPage === 1) {
                    document.getElementById('likesList').innerHTML = listHtml;
                } else {
                    document.getElementById('likesList').innerHTML += listHtml;
                }

                likesHasMore = data.pagination.has_more;
                likesCurrentPage++;
            }

            likesLoading = false;
            document.getElementById('likesLoading').style.display = 'none';
        })
        .catch(error => {
            likesLoading = false;
            document.getElementById('likesLoading').style.display = 'none';
        });
}

// Infinite scroll для модального окна
document.getElementById('likesModal')?.addEventListener('shown.bs.modal', function() {
    const modalBody = this.querySelector('.modal-body');

    modalBody.addEventListener('scroll', function() {
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - 50) {
            const search = document.getElementById('likesSearchInput').value;
            loadLikesPage(search);
        }
    });
});

// Поиск по никнейму
document.getElementById('likesSearchInput')?.addEventListener('input', function() {
    clearTimeout(likesSearchTimeout);

    likesSearchTimeout = setTimeout(() => {
        likesCurrentPage = 1;
        likesHasMore = true;
        likesLoading = false;
        document.getElementById('likesList').innerHTML = '';
        document.getElementById('likesEmpty').style.display = 'none';
        loadLikesPage(this.value);
    }, 500);
});

// Инициализация popovers и обработчиков для кнопок лайков
document.addEventListener('DOMContentLoaded', function() {
    initializeLikeButtons();
});

function initializeLikeButtons() {
    document.querySelectorAll('.like-topic-btn:not(.popover-initialized), .like-post-btn:not(.popover-initialized)').forEach(btn => {
        const likeCount = btn.querySelector('.like-count');
        const count = parseInt(likeCount?.textContent || 0);

        if (count > 0 && likeCount) {
            btn.classList.add('popover-initialized');

            let popover = null;
            let showTimeout = null;
            let hideTimeout = null;
            let contentLoaded = false;
            let cachedContent = '';

            // Загружаем контент заранее ОДИН раз
            loadLikesPopoverContent(btn).then(content => {
                cachedContent = content;
                contentLoaded = true;
            });

            btn.addEventListener('mouseenter', function() {
                clearTimeout(hideTimeout);

                showTimeout = setTimeout(() => {
                    // Создаем popover только если контент загружен
                    if (contentLoaded && !popover) {
                        popover = new bootstrap.Popover(btn, {
                            html: true,
                            trigger: 'manual',
                            placement: 'top',
                            customClass: 'likes-preview-popover',
                            content: cachedContent,
                            sanitize: false
                        });
                    }

                    if (popover) {
                        popover.show();

                        // Добавляем обработчики к popover
                        setTimeout(() => {
                            const popoverEl = document.querySelector('.popover.likes-preview-popover:not([data-events-set])');
                            if (popoverEl) {
                                popoverEl.dataset.eventsSet = 'true';

                                popoverEl.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
                                popoverEl.addEventListener('mouseleave', () => {
                                    hideTimeout = setTimeout(() => popover && popover.hide(), 300);
                                });
                            }
                        }, 50);
                    }
                }, 500);
            });

            btn.addEventListener('mouseleave', function() {
                clearTimeout(showTimeout);
                hideTimeout = setTimeout(() => {
                    if (popover) popover.hide();
                }, 300);
            });

            // Клик по счетчику
            likeCount.style.cursor = 'pointer';
            likeCount.title = 'Нажмите, чтобы посмотреть всех кто поставил лайк';

            if (!likeCount.hasAttribute('data-click-initialized')) {
                likeCount.setAttribute('data-click-initialized', 'true');
                likeCount.addEventListener('click', function(e) {
                    e.stopPropagation();
                    e.preventDefault();

                    clearTimeout(showTimeout);
                    clearTimeout(hideTimeout);
                    if (popover) popover.hide();

                    const type = btn.dataset.likeType;
                    const id = btn.dataset.likeId;
                    openLikesModal(type, id);
                });
            }
        }
    });
}

// Добавляем функцию escapeHtml если её нет
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<style>
.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}

.likes-preview-popover {
    max-width: 320px !important;
}

.likes-preview-popover .popover-body {
    padding: 0.75rem;
}

.likes-preview {
    max-width: 280px;
}

.like-count {
    transition: color 0.2s, text-decoration 0.2s;
}

.like-count:hover {
    color: var(--bs-primary) !important;
    text-decoration: underline;
}

/* Стили для кнопок лайков с popover */
</style>

@endsection