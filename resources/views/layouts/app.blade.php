<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <script>
        // Определение темы из системных настроек или localStorage
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = storedTheme || systemTheme;
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seoTitle ?? config('app.name') }}</title>
    <meta name="description" content="{{ $seoDescription ?? 'Форум разработчиков и IT-специалистов' }}">
    <meta name="keywords" content="{{ $seoKeywords ?? 'форум, разработка, программирование' }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">

    @if(isset($topic))
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "DiscussionForumPosting",
        "headline": "{{ \App\Helpers\SeoHelper::cleanText($topic->title) }}",
        "description": "{{ \App\Helpers\SeoHelper::cleanText($topic->content) }}",
        "author": {
            "@type": "Person",
            "name": "{{ $topic->user->username }}"
        },
        "datePublished": "{{ $topic->created_at->toISOString() }}",
        "url": "{{ url()->current() }}"
    }
    </script>
    @endif

    @stack('head')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.7/quill.bubble.css" rel="stylesheet">

<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=104018327', 'ym');

    ym(104018327, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/104018327" style="position:absolute; left:-9999px;" alt="" /></div></noscript>


    <link rel="stylesheet" href="/css/custom.css">
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
            <div class="container-ml container">

                <a class="navbar-brand d-flex align-items-center" href="{{ route('forum.index') }}">
                    <i class="bi bi-chat-dots me-2 fs-5"></i>
                    <span class="fw-bold">{{ config('app.name', 'Форум') }}</span>
                </a>

                <div class="d-flex d-lg-none align-items-center gap-2">
                    @auth
<div class="nav-item">
    <button class="nav-link text-light d-flex align-items-center px-2 py-1 rounded border-0 bg-transparent theme-toggle"
            onclick="toggleTheme()"
            title="Переключить тему">
        <i class="bi bi-moon-stars fs-5" id="themeIconMobile"></i>
    </button>
</div>

<div class="nav-item">
    <a class="nav-link text-light d-flex align-items-center px-2 py-1 rounded position-relative"
       href="{{ route('messages.index') }}"
       title="Сообщения">
        <i class="bi bi-chat-dots fs-5"></i>
        
        @php
            $unreadMessagesCount = auth()->user()->unreadMessagesCount();
        @endphp
        
        @if($unreadMessagesCount > 0)
            <span class="position-absolute badge rounded-pill bg-danger"
                  style="top: -2px; left: 16px; font-size: 0.65rem; padding: 0.25em 0.4em; min-width: 18px;">
                {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
            </span>
        @endif
    </a>
</div>

                    <div class="nav-item dropdown">
                        <a class="nav-link text-light d-flex align-items-center px-2 py-1 rounded position-relative"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                            onclick="markNotificationsAsViewed()">
                            <i class="bi bi-bell fs-5"></i>
                            @if(auth()->user()->notifications()->unread()->count() > 0)
                            <span class="position-absolute badge rounded-pill bg-danger"
                                  style="top: -2px; left: 16px; font-size: 0.65rem; padding: 0.25em 0.4em; min-width: 18px;">
                                {{ auth()->user()->notifications()->unread()->count() }}
                            </span>
                            @endif
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 320px;">
                            <li>
                                <h6 class="dropdown-header">Уведомления</h6>
                            </li>
                            @forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)
                            <li>
                                <a class="dropdown-item py-2 {{ $notification->is_read ? '' : 'bg-light' }}"
                                    href="{{ route('notifications.show', $notification) }}">
                                    <div class="d-flex align-items-start">
                                        <div class="position-relative me-2 flex-shrink-0">
                                            <img src="{{ $notification->fromUser->avatar_url ?? '/images/default-avatar.png' }}"
                                                class="rounded-circle" width="32" height="32" alt="Avatar"
                                                style="object-fit: cover;">
                                            <span
                                                class="position-absolute bottom-0 end-0 bg-white rounded-circle d-flex align-items-center justify-content-center border"
                                                style="width: 16px; height: 16px; transform: translate(25%, 25%);">
                                                <i class="bi {{ $notification->icon }} text-primary"
                                                    style="font-size: 8px;"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold small">{{ $notification->title }}</div>
                                            <div class="text-muted small">{{ Str::limit($notification->message, 60) }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $notification->created_at->diffForHumans() }}</div>
                                        </div>
                                        @if(!$notification->is_read)
                                        <div class="ms-2">
                                            <span class="badge bg-primary rounded-pill"
                                                style="width: 8px; height: 8px; padding: 0;"></span>
                                        </div>
                                        @endif
                                    </div>
                                </a>
                            </li>
                            @empty
                            <li>
                                <div class="dropdown-item text-muted text-center">Нет новых уведомлений</div>
                            </li>
                            @endforelse
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">Все
                                    уведомления</a></li>
                        </ul>
                    </div>

                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light d-flex align-items-center px-2 py-1 rounded"
                            href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ auth()->user()->avatar_url }}" class="rounded-circle" width="28" height="28"
                                alt="Avatar">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isStaff())
                            <li>
                                <a class="dropdown-item" href="{{ route('moderation.index') }}">
                                    <i class="bi bi-shield-check text-warning me-2"></i>
                                    Модерация
                                    @if(auth()->user()->isAdmin())
                                    <span class="badge bg-danger ms-1">Админ</span>
                                    @else
                                    <span class="badge bg-warning text-dark ms-1">Мод</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.show', auth()->user()) }}">
                                    <i class="bi bi-person me-2"></i>Мой профиль
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-gear me-2"></i>Настройки
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('bookmarks.index') }}">
                                    <i class="bi bi-bookmark me-2"></i>Закладки
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="bi bi-box-arrow-right me-2"></i>Выйти
                                </a>
                            </li>
                        </ul>
                    </div>
                    @else
                    <div class="nav-item">
                        <a class="nav-link text-light d-flex align-items-center px-2 py-1 rounded"
                            href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                        </a>
                    </div>
                    @endauth

                    <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">

                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        @auth
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center px-lg-3 py-2 rounded"
                                href="{{ route('topics.create') }}">
                                <i class="bi bi-plus-circle me-2"></i>
                                <span>Создать тему</span>
                            </a>
                        </li>
                        @endauth

                        <li class="nav-item">
                            <form class="d-flex me-3" method="GET" action="{{ route('search') }}" role="search">
                                <div class="input-group">
                                    <input class="form-control" type="search" name="q" placeholder="Поиск..."
                                        aria-label="Поиск" value="{{ request('q') }}" minlength="2">
                                    <button class="btn btn-outline-secondary" type="submit" title="Поиск">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>

                        </li>
                    </ul>
                    <ul class="navbar-nav ms-auto d-none d-lg-flex">
                        @guest
                        @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center px-lg-3 py-2 rounded"
                                href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                <span>Вход</span>
                            </a>
                        </li>
                        @endif

                        @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center px-lg-3 py-2 rounded border border-light"
                                href="{{ route('register') }}">
                                <i class="bi bi-person-plus me-2"></i>
                                <span>Регистрация</span>
                            </a>
                        </li>
                        @endif
                        @else
                        <li class="nav-item dropdown">
                            <a class="nav-link d-flex position-relative"
                                href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                                onclick="markNotificationsAsViewed()" style=" font-size: 21px; ">
                                <i class="bi bi-bell me-2"></i>
                                @if(auth()->user()->notifications()->unread()->count() > 0)
                                <span id="notification-badge"
                                      class="position-absolute badge rounded-pill bg-danger"
                                      style="top: 4px; left: 14px; font-size: 0.65rem; padding: 0.25em 0.4em; min-width: 18px;">
                                    {{ auth()->user()->notifications()->unread()->count() }}
                                </span>
                                @endif
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 320px;">
                                <li>
                                    <h6 class="dropdown-header">Уведомления</h6>
                                </li>
                                @forelse(auth()->user()->notifications()->latest()->limit(5)->get() as $notification)
                                <li>
                                    <a class="dropdown-item py-2 {{ $notification->is_read ? '' : 'bg-light' }}"
                                        href="{{ route('notifications.show', $notification) }}">
                                        <div class="d-flex align-items-start">
                                            <div class="position-relative me-2 flex-shrink-0">
                                                <img src="{{ $notification->fromUser->avatar_url ?? '/images/default-avatar.png' }}"
                                                    class="rounded-circle" width="32" height="32" alt="Avatar"
                                                    style="object-fit: cover;">
                                                <span
                                                    class="position-absolute bottom-0 end-0 bg-white rounded-circle d-flex align-items-center justify-content-center border"
                                                    style="width: 16px; height: 16px; transform: translate(25%, 25%);">
                                                    <i class="bi {{ $notification->icon }} text-primary"
                                                        style="font-size: 8px;"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold small">{{ $notification->title }}</div>
                                                <div class="text-muted small">
                                                    {{ Str::limit($notification->message, 60) }}</div>
                                                <div class="text-muted small">
                                                    {{ $notification->created_at->diffForHumans() }}</div>
                                            </div>
                                            @if(!$notification->is_read)
                                            <div class="ms-2">
                                                <span class="badge bg-primary rounded-pill"
                                                    style="width: 8px; height: 8px; padding: 0;"></span>
                                            </div>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                                @empty
                                <li>
                                    <div class="dropdown-item text-muted text-center">Нет новых уведомлений</div>
                                </li>
                                @endforelse
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">Все
                                        уведомления</a></li>
                            </ul>
                        </li>
<li class="nav-item">
    <a class="nav-link d-flex position-relative"
       href="{{ route('messages.index') }}" style=" font-size: 21px; ">
        <i class="bi bi-chat-dots me-2"></i>

        @php
            $unreadMessagesCount = auth()->user()->unreadMessagesCount();
        @endphp

        @if($unreadMessagesCount > 0)
            <span class="position-absolute badge rounded-pill bg-danger"
                  style="top: 4px; left: 14px; font-size: 0.65rem; padding: 0.25em 0.4em; min-width: 18px;">
                {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
            </span>
        @endif
    </a>
</li>

                        <li class="nav-item">
                            <a class="nav-link d-flex"
                                href="{{ route('bookmarks.index') }}" style=" font-size: 21px; ">
                                <i class="bi bi-bookmarks me-2"></i>
                            </a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center px-lg-3 py-2 rounded" href="#"
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{ auth()->user()->avatar_url }}" class="rounded-circle me-2" width="32"
                                    height="32" alt="Avatar">
                                <span>{{ auth()->user()->username }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if(auth()->user()->isStaff())
                                <li>
                                    <a class="dropdown-item" href="{{ route('moderation.index') }}">
                                        <i class="bi bi-shield-check text-warning me-2"></i>
                                        Модерация
                                        @if(auth()->user()->isAdmin())
                                        <span class="badge bg-danger ms-1">Админ</span>
                                        @else
                                        <span class="badge bg-warning text-dark ms-1">Мод</span>
                                        @endif
                                    </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.show', auth()->user()) }}">
                                        <i class="bi bi-person me-2"></i>Мой профиль
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-gear me-2"></i>Настройки
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('bookmarks.index') }}">
                                        <i class="bi bi-bookmark me-2"></i>Закладки
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="toggleTheme()">
                                        <i class="bi bi-moon-stars me-2" id="themeIconDropdown"></i>
                                        <span id="themeTextDropdown">Темная тема</span>
                                    </button>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i>Выйти
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <main class="py-4">
            <div class="container-ml container">
                @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @auth
    <script>
    function markNotificationsAsViewed() {
        // Отправляем запрос на сервер для отметки уведомлений как просмотренных
        fetch('{{ route("notifications.markDropdownViewed") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notification-badge');
                    if (badge) {
                        badge.style.display = 'none';
                    }

                    const unreadItems = document.querySelectorAll('.dropdown-item.bg-light');
                    unreadItems.forEach(item => {
                        item.classList.remove('bg-light');
                    });

                    const unreadDots = document.querySelectorAll('.badge.bg-primary.rounded-pill');
                    unreadDots.forEach(dot => {
                        if (dot.style.width === '8px') { // только точки-индикаторы
                            dot.style.display = 'none';
                        }
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
    </script>

    @if(auth()->user()->isStaff())
    <script>
    function showDeleteTopicModal(topicId, topicTitle) {
        document.getElementById('deleteTopicTitle').textContent = topicTitle;
        document.getElementById('deleteTopicForm').action = `/moderation/topics/${topicId}`;
        document.getElementById('no_notification').checked = false;
        document.getElementById('reason').value = '';
        document.getElementById('reasonField').style.display = 'block';
        new bootstrap.Modal(document.getElementById('deleteTopicModal')).show();
    }

    function showMoveTopicModal(topicId, topicTitle) {
        document.getElementById('moveTopicTitle').textContent = topicTitle;
        document.getElementById('moveTopicForm').action = `/moderation/topics/${topicId}/move`;
        document.getElementById('category_id').value = '';
        document.getElementById('move_reason').value = '';
        new bootstrap.Modal(document.getElementById('moveTopicModal')).show();
    }

    function showDeletePostModal(postId) {
        document.getElementById('deletePostForm').action = `/moderation/posts/${postId}`;
        // Сбрасываем форму
        document.getElementById('post_no_notification').checked = false;
        document.getElementById('post_reason').value = '';
        document.getElementById('postReasonField').style.display = 'block';
        new bootstrap.Modal(document.getElementById('deletePostModal')).show();
    }

    function togglePostReasonField() {
        const checkbox = document.getElementById('post_no_notification');
        const reasonField = document.getElementById('postReasonField');
        const reasonTextarea = document.getElementById('post_reason');

        if (checkbox.checked) {
            reasonField.style.display = 'none';
            reasonTextarea.value = '';
        } else {
            reasonField.style.display = 'block';
        }
    }
    </script>
    @endif

    @endauth
    @auth
    <script>
    function showToast(type, title, message, duration = 5000) {
        const toastContainer = document.querySelector('.toast-container');

        const typeConfig = {
            success: {
                bgClass: 'bg-success',
                textClass: 'text-white',
                icon: 'bi-check-circle-fill'
            },
            error: {
                bgClass: 'bg-danger',
                textClass: 'text-white',
                icon: 'bi-exclamation-triangle-fill'
            },
            warning: {
                bgClass: 'bg-warning',
                textClass: 'text-dark',
                icon: 'bi-exclamation-circle-fill'
            },
            info: {
                bgClass: 'bg-info',
                textClass: 'text-white',
                icon: 'bi-info-circle-fill'
            }
        };

        const config = typeConfig[type] || typeConfig.info;
        const toastId = 'toast-' + Date.now();

        const toastHTML = `
                <div id="${toastId}" class="toast ${config.bgClass} ${config.textClass}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header ${config.bgClass} ${config.textClass} border-0">
                        <i class="bi ${config.icon} me-2"></i>
                        <strong class="me-auto">${title}</strong>
                        <button type="button" class="btn-close ${config.textClass === 'text-white' ? 'btn-close-white' : ''}" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            delay: duration
        });

        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        return toast;
    }

    function handleAjaxResponse(response, data) {
        if (response.ok) {
            if (data.success) {
                showToast('success', 'Успех!', data.message || 'Операция выполнена успешно');
            } else if (data.error) {
                showToast('error', 'Ошибка!', data.error);
            } else {
                showToast('info', 'Информация', data.message || 'Операция выполнена');
            }
        } else {
            if (response.status === 422) {
                showToast('warning', 'Ошибка валидации', 'Проверьте корректность введенных данных');
            } else if (response.status === 429) {
                showToast('warning', 'Слишком много запросов', 'Подождите немного перед следующей попыткой');
            } else if (response.status >= 500) {
                showToast('error', 'Ошибка сервера', 'Произошла внутренняя ошибка сервера');
            } else {
                showToast('error', 'Ошибка!', `HTTP ${response.status}: ${response.statusText}`);
            }
        }
    }
    </script>
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <!-- Скрипт кастомных смайликов для Quill Editor -->
    <script src="{{ asset('js/quill-emojis.js') }}"></script>

    @yield('scripts')
    @endauth

    @stack('scripts')

    @if(optional(auth()->user())->isStaff())
    <div class="modal fade" id="deleteTopicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteTopicForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>Удаление темы
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Внимание!</strong> Это действие нельзя отменить. Тема и все её сообщения будут
                            удалены навсегда.
                        </div>

                        <p>Вы собираетесь удалить тему: <strong id="deleteTopicTitle"></strong></p>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="no_notification" name="no_notification"
                                onchange="toggleReasonField()">
                            <label class="form-check-label" for="no_notification">
                                Удалить без уведомления автора
                            </label>
                            <div class="form-text">Если отмечено, автор темы не получит уведомление об удалении</div>
                        </div>

                        <div class="mb-3" id="reasonField">
                            <label for="reason" class="form-label">Причина удаления</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3"
                                placeholder="Укажите причину удаления темы (автор получит уведомление)"></textarea>
                            <div class="form-text">Если причина указана, автор темы получит уведомление с объяснением.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Удалить тему
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveTopicModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="moveTopicForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Переместить тему</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Переместить тему "<span id="moveTopicTitle"></span>" в другую категорию:</p>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Новая категория</label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">Выберите категорию</option>
                                @foreach(\App\Models\Category::all() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="move_reason" class="form-label">Причина перемещения (необязательно)</label>
                            <textarea name="reason" id="move_reason" class="form-control" rows="2"
                                placeholder="Укажите причину перемещения"></textarea>
                            <div class="form-text">Если причина указана, автор темы получит уведомление с объяснением.
                            </div>
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
    @endif

    <script>
        // Функция переключения темы
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            updateThemeIcon();
        }

        // Обновление иконки темы
        function updateThemeIcon() {
            const theme = document.documentElement.getAttribute('data-bs-theme');
            const iconMobile = document.getElementById('themeIconMobile');
            const iconDropdown = document.getElementById('themeIconDropdown');
            const textDropdown = document.getElementById('themeTextDropdown');

            const iconClass = theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars';
            const themeText = theme === 'dark' ? 'Светлая тема' : 'Темная тема';

            if (iconMobile) {
                iconMobile.className = `bi ${iconClass} fs-5`;
            }
            if (iconDropdown) {
                iconDropdown.className = `bi ${iconClass} me-2`;
            }
            if (textDropdown) {
                textDropdown.textContent = themeText;
            }
        }

        // Инициализация иконки при загрузке
        document.addEventListener('DOMContentLoaded', updateThemeIcon);

        // Отслеживание изменений системной темы
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                const newTheme = e.matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                updateThemeIcon();
            }
        });
    </script>

</body>

</html>