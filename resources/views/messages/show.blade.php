@extends('layouts.app')

@section('title', 'Диалог с ' . $otherUser->name)

@section('content')
<div class="container-fluid p-0" style="height: calc(100vh - 56px);">
    <div class="row g-0 h-100">
        <!-- Список диалогов (сайдбар) -->
        <div class="col-md-4 col-lg-3 border-end h-100 d-none d-md-flex flex-column bg-white">
            <!-- Заголовок -->
            <div class="border-bottom p-3 bg-light">
                <h5 class="mb-0">
                    <i class="bi bi-chat-dots-fill me-2"></i>Сообщения
                </h5>
            </div>

            <!-- Список диалогов -->
            <div class="flex-grow-1 overflow-auto">
                <div class="list-group list-group-flush">
                    @foreach($conversations as $conv)
                        @php
                            $user = $conv->otherUser;
                            $lastMsg = $conv->lastMsg;
                            $unread = $conv->unreadCount;
                            $isActive = $user->id == $otherUser->id;
                        @endphp
                        
                        <a href="{{ route('messages.show', $user->id) }}" 
                           class="list-group-item list-group-item-action border-0 py-3 {{ $isActive ? 'active' : ($unread > 0 ? 'bg-light' : '') }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-2">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}" 
                                             alt="{{ $user->name }}" 
                                             class="rounded-circle"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="rounded-circle {{ $isActive ? 'bg-white text-primary' : 'bg-secondary text-white' }} d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between mb-1">
                                        <h6 class="mb-0 small {{ !$isActive && $unread > 0 ? 'fw-bold' : '' }}">
                                            {{ Str::limit($user->name, 18) }}
                                        </h6>
                                        @if($unread > 0 && !$isActive)
                                            <span class="badge bg-primary rounded-pill">{{ $unread > 9 ? '9+' : $unread }}</span>
                                        @endif
                                    </div>

                                    @if($lastMsg)
                                        <p class="mb-0 small text-truncate {{ $isActive ? 'text-white-50' : ($unread > 0 ? 'fw-semibold' : 'text-muted') }}">
                                            @if($lastMsg->hasImage())
                                                <i class="bi bi-image"></i>
                                            @endif
                                            {{ $lastMsg->content ? Str::limit($lastMsg->content, 20) : 'Фото' }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Область чата -->
        <div class="col-12 col-md-8 col-lg-9 d-flex flex-column bg-white">
            <!-- Шапка чата -->
            <div class="border-bottom p-3 bg-light">
                <div class="d-flex align-items-center">
                    <!-- Кнопка назад (мобильные) -->
                    <a href="{{ route('messages.index') }}" class="btn btn-link text-dark p-0 me-3 d-md-none">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>

                    <!-- Аватар -->
                    @if($otherUser->avatar)
                        <img src="{{ asset('storage/' . $otherUser->avatar) }}" 
                             alt="{{ $otherUser->name }}" 
                             class="rounded-circle me-3"
                             style="width: 40px; height: 40px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3"
                             style="width: 40px; height: 40px;">
                            {{ strtoupper(substr($otherUser->name, 0, 1)) }}
                        </div>
                    @endif

                    <!-- Инфо -->
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $otherUser->name }}</h6>
                        <small class="text-muted">
                            @if($otherUser->last_activity_at && $otherUser->last_activity_at->diffInMinutes() < 5)
                                <span class="text-success">онлайн</span>
                            @else
                                {{ $otherUser->last_activity_at ? $otherUser->last_activity_at->diffForHumans() : 'не в сети' }}
                            @endif
                        </small>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleSearch()">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="{{ route('profile.show', $otherUser->id) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-person"></i>
                        </a>
                    </div>
                </div>

                <!-- Панель поиска (скрыта по умолчанию) -->
                <div id="searchPanel" class="mt-3" style="display: none;">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="searchInput" 
                               placeholder="Поиск по сообщениям...">
                        <button class="btn btn-outline-secondary" type="button" onclick="searchMessages()">
                            <i class="bi bi-search"></i>
                        </button>
                        <button class="btn btn-outline-danger" type="button" onclick="clearSearch()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="mt-2"></div>
                </div>
            </div>

            <!-- Область сообщений -->
            <div class="flex-grow-1 overflow-auto p-3" id="messagesContainer">
                <div id="loadMoreBtn" class="text-center mb-3" style="display: none;">
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadMoreMessages()">
                        <i class="bi bi-arrow-up-circle"></i> Загрузить историю
                    </button>
                </div>

                <div id="messagesWrapper">
                    @if(empty($groupedMessages))
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-chat-heart display-1 d-block mb-3"></i>
                            <p>Начните диалог с {{ $otherUser->name }}</p>
                        </div>
                    @else
                        @foreach($groupedMessages as $dateLabel => $messages)
                            <!-- Разделитель даты -->
                            <div class="text-center my-3">
                                <span class="badge bg-light text-dark border">{{ $dateLabel }}</span>
                            </div>

                            <!-- Сообщения за эту дату -->
                            @foreach($messages as $message)
                                @php
                                    $isMine = $message->sender_id == auth()->id();
                                @endphp

                                <div class="mb-2 d-flex {{ $isMine ? 'justify-content-end' : 'justify-content-start' }}" data-message-id="{{ $message->id }}">
                                    <div class="{{ $isMine ? 'bg-primary text-white' : 'bg-light' }} p-2 rounded" style="max-width: 70%;">
                                        @if($message->hasImage())
                                            <div class="mb-2">
                                                <img src="{{ $message->imageUrl }}" 
                                                     alt="Фото" 
                                                     class="img-fluid rounded"
                                                     style="max-width: 100%; cursor: pointer;"
                                                     onclick="window.open(this.src, '_blank')">
                                            </div>
                                        @endif

                                        @if($message->content)
                                            <div style="word-wrap: break-word; white-space: pre-wrap;">{{ $message->content }}</div>
                                        @endif

                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                            <small class="{{ $isMine ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.75rem;">
                                                {{ $message->created_at->format('H:i') }}
                                            </small>

                                            @if($isMine)
                                                <span class="ms-2">
                                                    @if($message->is_read)
                                                        <i class="bi bi-check-all text-white"></i>
                                                    @else
                                                        <i class="bi bi-check text-white-50"></i>
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Закрепленная форма отправки -->
            <div class="border-top p-3 bg-light" style="position: sticky; bottom: 0;">
                <form id="messageForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $otherUser->id }}">

                    <div class="d-flex align-items-end gap-2">
                        <!-- Прикрепить фото -->
                        <label for="imageInput" class="btn btn-outline-secondary" title="Прикрепить фото">
                            <i class="bi bi-image"></i>
                            <input type="file" 
                                   id="imageInput" 
                                   name="image" 
                                   accept="image/*" 
                                   class="d-none"
                                   onchange="previewImage(this)">
                        </label>

                        <!-- Текст -->
                        <div class="flex-grow-1">
                            <textarea name="content" 
                                      id="messageContent"
                                      class="form-control" 
                                      rows="1" 
                                      placeholder="Сообщение..."
                                      style="resize: none; max-height: 100px;"
                                      onkeydown="handleEnter(event)"
                                      oninput="autoResize(this)"></textarea>

                            <!-- Превью изображения -->
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <div class="position-relative d-inline-block">
                                    <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-height: 80px;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" style="padding: 0.2rem 0.4rem;" onclick="removeImage()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Отправить -->
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Переменные
const conversationId = {{ $conversation->id }};
const userId = {{ $otherUser->id }};
let oldestMessageId = null;
let isLoadingMore = false;

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    updateOldestMessageId();
    checkIfCanLoadMore();
});

// Автопрокрутка вниз
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Авторазмер textarea
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
}

// Enter для отправки
function handleEnter(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submitMessage();
    }
}

// Отправка сообщения (AJAX)
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitMessage();
});

function submitMessage() {
    const form = document.getElementById('messageForm');
    const content = document.getElementById('messageContent').value.trim();
    const imageInput = document.getElementById('imageInput');

    if (!content && !imageInput.files.length) {
        return;
    }

    // Создаем FormData и добавляем все данные формы
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('user_id', '{{ $otherUser->id }}');
    formData.append('content', content);

    // Добавляем изображение, если оно есть
    if (imageInput.files.length > 0) {
        formData.append('image', imageInput.files[0]);
    }

    fetch('{{ route("messages.store", $otherUser->id) }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Очистить форму
            document.getElementById('messageContent').value = '';
            document.getElementById('messageContent').style.height = 'auto';
            removeImage();

            // Добавить сообщение в конец
            appendMessage(data.message, true);
            scrollToBottom();
        } else if (data.error) {
            alert(data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при отправке сообщения: ' + error.message);
    });
}

// Добавить сообщение в DOM
function appendMessage(message, isMine) {
    const messagesWrapper = document.getElementById('messagesWrapper');
    const today = new Date().toDateString();
    const messageDate = new Date(message.created_at).toDateString();
    
    let dateLabel = '';
    if (messageDate === today) {
        dateLabel = 'Сегодня';
    } else {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        if (messageDate === yesterday.toDateString()) {
            dateLabel = 'Вчера';
        } else {
            const options = { day: 'numeric', month: 'long' };
            dateLabel = new Date(message.created_at).toLocaleDateString('ru-RU', options);
        }
    }

    // Проверяем, есть ли уже разделитель с этой датой
    const existingDateLabel = messagesWrapper.querySelector(`[data-date-label="${dateLabel}"]`);
    
    if (!existingDateLabel) {
        const dateSeparator = document.createElement('div');
        dateSeparator.className = 'text-center my-3';
        dateSeparator.setAttribute('data-date-label', dateLabel);
        dateSeparator.innerHTML = `<span class="badge bg-light text-dark border">${dateLabel}</span>`;
        messagesWrapper.appendChild(dateSeparator);
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = `mb-2 d-flex ${isMine ? 'justify-content-end' : 'justify-content-start'}`;
    messageDiv.setAttribute('data-message-id', message.id);

    const bubbleClass = isMine ? 'bg-primary text-white' : 'bg-light';
    const timeClass = isMine ? 'text-white-50' : 'text-muted';
    
    let imageHtml = '';
    if (message.image_path) {
        const imageUrl = '/storage/' + message.image_path;
        imageHtml = `
            <div class="mb-2">
                <img src="${imageUrl}" alt="Фото" class="img-fluid rounded" style="max-width: 100%; cursor: pointer;" onclick="window.open(this.src, '_blank')">
            </div>
        `;
    }

    const contentHtml = message.content ? `<div style="word-wrap: break-word; white-space: pre-wrap;">${escapeHtml(message.content)}</div>` : '';
    
    const time = new Date(message.created_at).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    
    const readStatus = isMine ? `
        <span class="ms-2">
            ${message.is_read ? '<i class="bi bi-check-all text-white"></i>' : '<i class="bi bi-check text-white-50"></i>'}
        </span>
    ` : '';

    messageDiv.innerHTML = `
        <div class="${bubbleClass} p-2 rounded" style="max-width: 70%;">
            ${imageHtml}
            ${contentHtml}
            <div class="d-flex align-items-center justify-content-between mt-1">
                <small class="${timeClass}" style="font-size: 0.75rem;">${time}</small>
                ${readStatus}
            </div>
        </div>
    `;

    messagesWrapper.appendChild(messageDiv);
}

// Превью изображения
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Удалить превью
function removeImage() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('previewImg').src = '';
}

// Загрузка истории сообщений
function updateOldestMessageId() {
    const messages = document.querySelectorAll('[data-message-id]');
    if (messages.length > 0) {
        oldestMessageId = messages[0].getAttribute('data-message-id');
    }
}

function checkIfCanLoadMore() {
    // Если сообщений больше 30, показываем кнопку загрузки
    const messages = document.querySelectorAll('[data-message-id]');
    if (messages.length >= 30) {
        document.getElementById('loadMoreBtn').style.display = 'block';
    }
}

function loadMoreMessages() {
    if (isLoadingMore || !oldestMessageId) return;
    
    isLoadingMore = true;
    const btn = document.querySelector('#loadMoreBtn button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Загрузка...';
    btn.disabled = true;

    fetch(`/messages/conversation/${conversationId}/load-more?before=${oldestMessageId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages) {
            const container = document.getElementById('messagesContainer');
            const scrollHeightBefore = container.scrollHeight;

            // Добавляем старые сообщения в начало
            prependMessages(data.messages);
            updateOldestMessageId();

            // Восстанавливаем позицию скролла
            const scrollHeightAfter = container.scrollHeight;
            container.scrollTop = scrollHeightAfter - scrollHeightBefore;

            // Скрываем кнопку если больше нет сообщений
            if (!data.hasMore) {
                document.getElementById('loadMoreBtn').style.display = 'none';
            }
        }

        btn.innerHTML = originalHtml;
        btn.disabled = false;
        isLoadingMore = false;
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        isLoadingMore = false;
    });
}

function prependMessages(groupedMessages) {
    const messagesWrapper = document.getElementById('messagesWrapper');
    
    // Создаем временный контейнер
    const tempDiv = document.createElement('div');
    
    for (const [dateLabel, messages] of Object.entries(groupedMessages)) {
        // Добавляем разделитель даты
        const dateSeparator = document.createElement('div');
        dateSeparator.className = 'text-center my-3';
        dateSeparator.setAttribute('data-date-label', dateLabel);
        dateSeparator.innerHTML = `<span class="badge bg-light text-dark border">${dateLabel}</span>`;
        tempDiv.appendChild(dateSeparator);

        // Добавляем сообщения
        messages.forEach(message => {
            const isMine = message.sender_id === {{ auth()->id() }};
            const messageDiv = createMessageElement(message, isMine);
            tempDiv.appendChild(messageDiv);
        });
    }

    // Вставляем в начало
    messagesWrapper.insertBefore(tempDiv, messagesWrapper.firstChild);
}

function createMessageElement(message, isMine) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `mb-2 d-flex ${isMine ? 'justify-content-end' : 'justify-content-start'}`;
    messageDiv.setAttribute('data-message-id', message.id);

    const bubbleClass = isMine ? 'bg-primary text-white' : 'bg-light';
    const timeClass = isMine ? 'text-white-50' : 'text-muted';
    
    let imageHtml = '';
    if (message.image_path) {
        const imageUrl = '/storage/' + message.image_path;
        imageHtml = `
            <div class="mb-2">
                <img src="${imageUrl}" alt="Фото" class="img-fluid rounded" style="max-width: 100%; cursor: pointer;" onclick="window.open(this.src, '_blank')">
            </div>
        `;
    }

    const contentHtml = message.content ? `<div style="word-wrap: break-word; white-space: pre-wrap;">${escapeHtml(message.content)}</div>` : '';
    
    const time = new Date(message.created_at).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    
    const readStatus = isMine ? `
        <span class="ms-2">
            ${message.is_read ? '<i class="bi bi-check-all text-white"></i>' : '<i class="bi bi-check text-white-50"></i>'}
        </span>
    ` : '';

    messageDiv.innerHTML = `
        <div class="${bubbleClass} p-2 rounded" style="max-width: 70%;">
            ${imageHtml}
            ${contentHtml}
            <div class="d-flex align-items-center justify-content-between mt-1">
                <small class="${timeClass}" style="font-size: 0.75rem;">${time}</small>
                ${readStatus}
            </div>
        </div>
    `;

    return messageDiv;
}

// Поиск по сообщениям
function toggleSearch() {
    const panel = document.getElementById('searchPanel');
    const isVisible = panel.style.display !== 'none';
    panel.style.display = isVisible ? 'none' : 'block';
    
    if (!isVisible) {
        document.getElementById('searchInput').focus();
    } else {
        clearSearch();
    }
}

function searchMessages() {
    const query = document.getElementById('searchInput').value.trim();
    
    if (!query) {
        clearSearch();
        return;
    }

    fetch(`/messages/conversation/${conversationId}/search?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.results, data.count);
        }
    })
    .catch(error => console.error('Error:', error));
}

function displaySearchResults(results, count) {
    const resultsDiv = document.getElementById('searchResults');
    
    if (count === 0) {
        resultsDiv.innerHTML = '<div class="alert alert-info mb-0 mt-2">Ничего не найдено</div>';
        return;
    }

    let html = `<div class="alert alert-success mb-0 mt-2">Найдено: ${count}</div>`;
    html += '<div class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">';
    
    results.forEach(msg => {
        const time = new Date(msg.created_at).toLocaleString('ru-RU', { 
            day: 'numeric', 
            month: 'short', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="scrollToMessage(${msg.id}); return false;">
                <div class="d-flex justify-content-between">
                    <strong class="mb-1">${msg.sender_id === {{ auth()->id() }} ? 'Вы' : '{{ $otherUser->name }}'}</strong>
                    <small class="text-muted">${time}</small>
                </div>
                <p class="mb-0 small text-truncate">${escapeHtml(msg.content || 'Фото')}</p>
            </a>
        `;
    });
    
    html += '</div>';
    resultsDiv.innerHTML = html;
}

function scrollToMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        messageElement.style.backgroundColor = '#fff3cd';
        setTimeout(() => {
            messageElement.style.backgroundColor = '';
        }, 2000);
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('searchResults').innerHTML = '';
}

// Поиск при нажатии Enter
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchMessages();
    }
});

// Экранирование HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>

<style>
/* Убираем все анимации */
*, *::before, *::after {
    transition: none !important;
    animation: none !important;
}

/* Кастомный скроллбар */
#messagesContainer::-webkit-scrollbar {
    width: 6px;
}

#messagesContainer::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#messagesContainer::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

#messagesContainer::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Активный диалог */
.list-group-item.active {
    z-index: 2;
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Адаптивность */
@media (max-width: 767.98px) {
    .container-fluid {
        height: calc(100vh - 56px) !important;
    }
}
</style>
@endsection