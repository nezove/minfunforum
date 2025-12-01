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
            <button class="btn btn-outline-primary btn-sm like-topic-btn" data-topic-id="{{ $topic->id }}">
                <i class="bi bi-heart{{ $topic->isLikedBy(auth()->id()) ? '-fill text-danger' : '' }}"></i>
                <span class="like-count ms-1">{{ $topic->likes_count }}</span>
            </button>
        @else
            {{-- Для автора показываем неактивную кнопку с объяснением --}}
            <button class="btn btn-outline-secondary btn-sm" disabled title="Нельзя лайкать собственную тему">
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
                        class="text-decoration-none text-primary fw-semibold">{{ $topic->user->username }}</a>
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
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="position-relative bg-dark d-flex align-items-center justify-content-center"
                                style="height: 500px;">
                                <img src="{{ $image->image_url }}" class="d-block img-fluid"
                                    style="max-height: 500px; max-width: 100%; object-fit: contain;"
                                    alt="{{ $image->original_name }}" loading="lazy">

                                <!-- Описание изображения -->
                                @if($image->description)
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2">
                                    <p class="mb-0">{{ $image->description }}</p>
                                </div>
                                @endif
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
                                        <small class="text-muted">{{ $file->formatted_size }}</small>
                                    </div>
                                    <div class="ms-2">
                                        <a href="{{ e($file->download_url) }}"
                                            class="btn btn-sm btn-outline-primary me-1">
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
                                        {{ $post->user->username }}
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
                    <div class="bg-light border-start border-3 border-primary p-2 mb-3 rounded-end">
                        <small class="text-muted mb-1 d-block">
                            <i class="bi bi-reply me-1"></i>В ответ на
                            <a href="{{ $post->replyToPost->permalink }}" class="text-decoration-none reply-link"
                                data-user="{{ $post->replyToPost->user->name }}">
                                {{ $post->replyToPost->user->name }}
                            </a>
                        </small>
                        <div class="small">
                            @php
                            $replyContent = \Illuminate\Support\Str::limit($post->replyToPost->content, 200);
                            $processedReply = \App\Helpers\MentionHelper::parseUserMentions($replyContent);
                            $processedReply = nl2br($processedReply, false);
                            $markdownReply = \Illuminate\Support\Str::markdown($processedReply);
                            $cleanReply = \Mews\Purifier\Facades\Purifier::clean($markdownReply, 'forum');

                            // Обработка изображений в цитатах
                            $cleanReply = preg_replace(
                            '/<img([^>]*?)>/i',
                                '<img$1 class="img-thumbnail rounded" loading="lazy"
                                    style="max-width:200px;height:auto;">',
                                    $cleanReply
                                    );
                                    @endphp
                                    {!! $cleanReply !!}
                        </div>
                    </div>
                    @endif

                    <!-- Цитата (если есть) -->
                    @if($post->quoted_content)
                    <div class="bg-light border-start border-3 border-secondary p-2 mb-3 rounded-end">
                        <small class="text-muted mb-1 d-block">
                            @if($post->parent)
                            <i class="bi bi-quote me-1"></i>{{ $post->parent->user->name }} писал:
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
                                                <small class="text-muted">{{ $file->formatted_size }}</small>
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
                <button class="btn btn-outline-primary btn-sm like-post-btn" data-post-id="{{ $post->id }}">
                    <i class="bi bi-heart{{ $post->isLikedBy(auth()->id()) ? '-fill text-danger' : '' }}"></i>
                    <span class="like-count ms-1">{{ $post->likes_count }}</span>
                </button>
            @else
                {{-- Для автора показываем неактивную кнопку с объяснением --}}
                <button class="btn btn-outline-secondary btn-sm" disabled title="Нельзя лайкать собственный пост">
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
                data-author="{{ $post->user->name }}">
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

                <div id="reply-preview" style="display: none;"
                    class="bg-light border-start border-3 border-primary p-2 mb-3 rounded-end">
                    <small class="text-muted mb-1 d-block">
                        <i class="bi bi-reply me-1"></i>В ответ на <span id="reply-author"></span>
                    </small>
                    <div class="small" id="reply-text"></div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="remove-reply">
                        <i class="bi bi-x"></i> Убрать ответ
                    </button>
                </div>

                <div class="card-body">
                    <div class="editor-container">
                        <!-- Quill Editor для ответов -->
                        <div id="quill-reply-editor" style="min-height: 150px;"></div>

                        <!-- Скрытое поле для отправки HTML -->
                        <textarea name="content" id="content" style="display: none;" required></textarea>

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

        // Валидация
        if (contentTextarea && contentTextarea.value.trim().length < 3) {
            showToast('warning', 'Слишком короткое сообщение', 'Сообщение должно содержать минимум 3 символа');
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
            console.error('Error:', error);

            if (error.status === 422) {
                // Ошибки валидации
                const errors = error.data.errors || {};
                let errorMessage = error.data.error || 'Проверьте правильность заполнения полей';

                if (errors.content) {
                    errorMessage = errors.content[0];
                }

                showToast('error', 'Ошибка валидации', errorMessage);
            } else if (error.status === 403) {
                // Доступ запрещён (бан или закрытая тема)
                showToast('error', 'Доступ запрещён', error.data.error || 'У вас нет прав для отправки сообщений');
            } else if (error.status === 429) {
                // Rate limiting
                showToast('warning', 'Слишком быстро', error.data.error || 'Подождите немного перед отправкой следующего сообщения');
            } else {
                // Общая ошибка
                showToast('error', 'Ошибка!', error.data?.error || 'Произошла ошибка при отправке сообщения');
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
        replyToPost(postId, author);
    });
});

// Обработчики для удаления ответов
document.getElementById('remove-reply')?.addEventListener('click', function() {
    document.getElementById('reply-preview').style.display = 'none';
    document.getElementById('reply_to_post_id').value = '';
});

// Функция для ответа на пост
function replyToPost(postId, author) {
    const textarea = document.getElementById('content');
    const replyPreview = document.getElementById('reply-preview');
    const replyAuthor = document.getElementById('reply-author');
    const replyText = document.getElementById('reply-text');
    const replyToPostIdInput = document.getElementById('reply_to_post_id');

    if (replyPreview && textarea) {
        replyAuthor.textContent = `@${author}`;
        replyText.textContent = `Ответ пользователю ${author}`;
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
        replyToHtml = `
            <div class="bg-light border-start border-3 border-primary p-2 mb-3 rounded-end">
                <small class="text-muted mb-1 d-block">
                    <i class="bi bi-reply me-1"></i>В ответ на
                    <a href="${post.reply_to_post.permalink}" class="text-decoration-none reply-link">
                        ${escapeHtml(post.reply_to_post.user.name)}
                    </a>
                </small>
                <div class="small">
                    ${post.reply_to_post.content.substring(0, 200)}${post.reply_to_post.content.length > 200 ? '...' : ''}
                </div>
            </div>`;
    }

    // Кнопки лайков и ответа
    let actionButtonsHtml = '';
    if (!isBanned) {
        if (post.user.id !== currentUserId) {
            actionButtonsHtml += `
                <button class="btn btn-outline-primary btn-sm like-post-btn" data-post-id="${post.id}">
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

        actionButtonsHtml += `
            <button class="btn btn-outline-secondary btn-sm reply-btn" data-post-id="${post.id}" data-author="${escapeHtml(post.user.name)}">
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

// Функция для экранирования HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
    if (!quillContent || quillContent.trim() === '<p><br></p>' || quillContent.trim() === '') return;

    const draft = {
        content: quillContent,
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

            // Устанавливаем HTML контент напрямую
            const delta = window.replyQuill.clipboard.convert(draft.content);
            window.replyQuill.setContents(delta, 'silent');
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
        const contentField = document.getElementById('content');
        if (contentField && contentField.value.trim().length > 3) {
            saveDraft();
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
    if (likeBtn) {
        likeBtn.addEventListener('click', handlePostLike);
    }

    // Кнопка ответа
    const replyBtn = newPost.querySelector('.reply-btn');
    if (replyBtn) {
        replyBtn.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const author = this.dataset.author;
            replyToPost(postId, author);
        });
    }
}
</script>
@endauth
@endsection
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
@endsection