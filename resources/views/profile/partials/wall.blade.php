<div class="wall-container">
    <!-- Форма создания записи на стене -->
    @auth
        @if(($user->allow_wall_posts || auth()->id() === $user->id || auth()->user()->isStaff()) && !auth()->user()->isBanned())
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-pencil-square me-2"></i>
                    @if(auth()->id() === $user->id)
                        Опубликовать у себя на стене
                    @else
                        Опубликовать на стене {{ $user->username }}
                    @endif
                </h6>
            </div>
            <div class="card-body">
                <form id="wall-post-form">
                    @csrf
                    <div class="mb-3">
                        <div id="wall-editor" style="min-height: 150px; background: white;"></div>
                        <input type="hidden" id="wall-content" name="content">
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Можно использовать @упоминания, добавлять изображения и смайлики
                        </small>
                        <button type="submit" class="btn btn-primary" id="wall-post-submit">
                            <i class="bi bi-send me-1"></i>Опубликовать
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @elseif(!$user->allow_wall_posts && auth()->id() !== $user->id && !auth()->user()->isStaff())
        <div class="alert alert-warning">
            <i class="bi bi-lock me-2"></i>
            Пользователь запретил публикации на своей стене
        </div>
        @endif
    @endauth

    @guest
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <a href="{{ route('login') }}">Войдите</a>, чтобы опубликовать запись на стене
    </div>
    @endguest

    <!-- Список постов на стене -->
    <div id="wall-posts-container">
        @forelse($wallPosts as $post)
            @include('profile.partials.wall-post', ['post' => $post, 'user' => $user])
        @empty
            <div class="card" id="empty-wall-message">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-journal-x display-4 d-block mb-3"></i>
                    <p class="mb-0">На стене пока нет записей</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Кнопка "Загрузить ещё" -->
    @if($wallPosts->hasMorePages())
    <div class="text-center mt-4" id="load-more-container">
        <button type="button" class="btn btn-outline-primary" id="load-more-btn" data-page="2">
            <i class="bi bi-arrow-down-circle me-1"></i>Загрузить ещё
        </button>
        <div class="spinner-border spinner-border-sm text-primary d-none" id="load-more-spinner" role="status">
            <span class="visually-hidden">Загрузка...</span>
        </div>
    </div>
    @endif
</div>

<!-- Модальное окно редактирования поста -->
<div class="modal fade" id="editWallPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edit-wall-editor" style="min-height: 150px; background: white;"></div>
                <input type="hidden" id="edit-wall-post-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-wall-post-edit">
                    <i class="bi bi-check-lg me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования комментария -->
<div class="modal fade" id="editWallCommentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать комментарий</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="edit-comment-textarea" rows="4"></textarea>
                <input type="hidden" id="edit-comment-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="save-comment-edit">
                    <i class="bi bi-check-lg me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно просмотра изображения -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index: 1"></button>
                <img id="modalImage" src="" class="img-fluid rounded" alt="Изображение">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/quill-forum.js') }}"></script>
<script src="{{ asset('js/quill-emojis.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Регистрируем кастомный формат для смайликов
    const Inline = Quill.import('blots/inline');
    class EmojiBlot extends Inline {
        static create(value) {
            let node = super.create();
            node.setAttribute('src', value.url || value);
            node.setAttribute('class', 'custom-emoji');
            node.setAttribute('width', '24');
            node.setAttribute('height', '24');
            node.setAttribute('style', 'width: 24px; height: 24px; display: inline; vertical-align: middle; margin: 0;');
            if (value.id) {
                node.setAttribute('data-emoji-id', value.id);
            }
            return node;
        }

        static value(node) {
            return {
                url: node.getAttribute('src'),
                id: node.getAttribute('data-emoji-id')
            };
        }
    }
    EmojiBlot.blotName = 'emoji';
    EmojiBlot.tagName = 'img';
    EmojiBlot.className = 'custom-emoji';
    Quill.register(EmojiBlot);

    // Инициализация Quill для создания поста
    const wallQuill = new Quill('#wall-editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: window.ForumQuill.toolbarOptions,
                handlers: {
                    'image': window.ForumQuill.imageHandler
                }
            },
            clipboard: {
                matchVisual: false,
                matchers: [
                    ['IMG', function(node, delta) {
                        // Если это смайлик, сохраняем его
                        if (node.classList.contains('custom-emoji')) {
                            return delta;
                        }
                        // Обычные изображения не копируем
                        return { ops: [] };
                    }]
                ]
            }
        },
        placeholder: 'Напишите что-нибудь...'
    });

    // Инициализация смайликов для wallQuill
    const initWallEmojis = () => {
        if (window.quillEmojis) {
            const wallContainer = document.querySelector('#wall-editor').parentElement;
            const wallToolbar = wallContainer.querySelector('.ql-toolbar');
            if (wallToolbar) {
                window.quillEmojis.initButton(wallQuill, wallToolbar);
                window.quillEmojis.initAutocomplete(wallQuill);

                // Обновляем текущий редактор при фокусе
                wallQuill.on('selection-change', function(range) {
                    if (range) {
                        window.quillEmojis.currentEditor = wallQuill;
                    }
                });
            }
        } else {
            // Повторяем попытку через 200ms если quillEmojis еще не загружен
            setTimeout(initWallEmojis, 200);
        }
    };
    setTimeout(initWallEmojis, 100);

    // Обработчик вставки изображений через Ctrl+V
    wallQuill.root.addEventListener('paste', function(e) {
        const clipboardData = e.clipboardData || window.clipboardData;
        const items = clipboardData.items;
        if (!items) return;

        let hasImage = false;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                hasImage = true;
                break;
            }
        }

        if (hasImage) {
            e.preventDefault();
            e.stopPropagation();
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                if (item.type.indexOf('image') !== -1) {
                    const file = item.getAsFile();
                    if (file) {
                        window.ForumQuill.uploadImage(file, wallQuill);
                    }
                }
            }
        }
    }, true);

    // Инициализация Quill для редактирования поста
    const editWallQuill = new Quill('#edit-wall-editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: window.ForumQuill.toolbarOptions,
                handlers: {
                    'image': window.ForumQuill.imageHandler
                }
            },
            clipboard: {
                matchVisual: false,
                matchers: [
                    ['IMG', function(node, delta) {
                        // Если это смайлик, сохраняем его
                        if (node.classList.contains('custom-emoji')) {
                            return delta;
                        }
                        // Обычные изображения не копируем
                        return { ops: [] };
                    }]
                ]
            }
        }
    });

    // Инициализация смайликов для editWallQuill
    const initEditEmojis = () => {
        if (window.quillEmojis) {
            const editContainer = document.querySelector('#edit-wall-editor').parentElement;
            const editToolbar = editContainer.querySelector('.ql-toolbar');
            if (editToolbar) {
                window.quillEmojis.initButton(editWallQuill, editToolbar);
                window.quillEmojis.initAutocomplete(editWallQuill);

                // Обновляем текущий редактор при фокусе
                editWallQuill.on('selection-change', function(range) {
                    if (range) {
                        window.quillEmojis.currentEditor = editWallQuill;
                    }
                });
            }
        } else {
            // Повторяем попытку через 200ms если quillEmojis еще не загружен
            setTimeout(initEditEmojis, 200);
        }
    };
    setTimeout(initEditEmojis, 100);

    // Обработчик кликов на изображения для открытия модального окна
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'IMG' && e.target.closest('.wall-post-content, .comment-content')) {
            const imgSrc = e.target.src;
            // Заменяем /thumbnails/ на /originals/ и _thumb на _original
            const originalUrl = e.target.getAttribute('data-original') ||
                               imgSrc.replace('/thumbnails/', '/originals/').replace('_thumb', '_original');

            document.getElementById('modalImage').src = originalUrl;
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }
    });

    // Создание поста на стене
    document.getElementById('wall-post-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const content = wallQuill.root.innerHTML;
        const textContent = wallQuill.getText().trim();

        if (textContent.length === 0) {
            showToast('warning', 'Ошибка', 'Пожалуйста, введите текст записи');
            return;
        }

        const submitBtn = document.getElementById('wall-post-submit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Публикация...';

        try {
            const response = await fetch('{{ route("wall.store", $user->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showToast('success', 'Успешно!', data.message);
                wallQuill.setContents([]);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', 'Ошибка', data.message || 'Не удалось опубликовать запись');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Ошибка', 'Произошла ошибка при публикации');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send me-1"></i>Опубликовать';
        }
    });

    // Редактирование поста
    window.editWallPost = function(postId) {
        const postContent = document.querySelector(`[data-post-id="${postId}"]`).innerHTML;
        document.getElementById('edit-wall-post-id').value = postId;
        editWallQuill.root.innerHTML = postContent;

        const modal = new bootstrap.Modal(document.getElementById('editWallPostModal'));
        modal.show();
    };

    // Сохранение редактирования поста
    document.getElementById('save-wall-post-edit').addEventListener('click', async function() {
        const postId = document.getElementById('edit-wall-post-id').value;
        const content = editWallQuill.root.innerHTML;

        if (editWallQuill.getText().trim().length === 0) {
            showToast('warning', 'Ошибка', 'Текст записи не может быть пустым');
            return;
        }

        try {
            const response = await fetch(`/wall/${postId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            });

            const data = await response.json();

            if (data.success) {
                showToast('success', 'Успешно!', data.message);
                bootstrap.Modal.getInstance(document.getElementById('editWallPostModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', 'Ошибка', data.message);
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось сохранить изменения');
        }
    });

    // Удаление поста
    window.deleteWallPost = async function(postId) {
        if (!confirm('Вы уверены, что хотите удалить эту запись?')) {
            return;
        }

        try {
            const response = await fetch(`/wall/${postId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                showToast('success', 'Успешно!', data.message);
                document.getElementById(`wall-post-${postId}`).remove();
            } else {
                showToast('error', 'Ошибка', data.message);
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось удалить запись');
        }
    };

    // Лайк поста
    window.toggleWallPostLike = async function(postId) {
        try {
            const response = await fetch(`/wall/${postId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                const likeBtn = document.querySelector(`#wall-post-${postId} .like-button`);
                const likeCount = document.querySelector(`#wall-post-${postId} .like-count`);

                if (data.liked) {
                    likeBtn.classList.add('text-danger');
                    likeBtn.querySelector('i').classList.replace('bi-heart', 'bi-heart-fill');
                    showToast('success', 'Лайк!', 'Вам понравилась эта запись');
                } else {
                    likeBtn.classList.remove('text-danger');
                    likeBtn.querySelector('i').classList.replace('bi-heart-fill', 'bi-heart');
                }

                likeCount.textContent = data.likes_count;
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось поставить лайк');
        }
    };

    // Переключить отображение комментариев (загрузка через AJAX)
    window.toggleComments = async function(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        const isLoaded = commentsSection.dataset.loaded === 'true';

        // Если комментарии уже загружены, просто переключаем видимость
        if (isLoaded) {
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                // Также показываем форму комментария
                showCommentForm(postId);
            } else {
                commentsSection.style.display = 'none';
            }
            return;
        }

        // Показываем секцию с индикатором загрузки
        commentsSection.style.display = 'block';
        const loadingDiv = commentsSection.querySelector('.comments-loading');
        const containerDiv = commentsSection.querySelector('.comments-container');

        loadingDiv.style.display = 'block';

        try {
            const response = await fetch(`/wall/${postId}/comments`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.success) {
                containerDiv.innerHTML = data.html;
                commentsSection.dataset.loaded = 'true';
                loadingDiv.style.display = 'none';

                // Показываем форму комментария
                showCommentForm(postId);

                // Обновляем счетчик, если он изменился
                const countSpan = document.querySelector(`#wall-post-${postId} .comments-count`);
                if (countSpan && data.count !== undefined) {
                    countSpan.textContent = data.count;
                }
            } else {
                throw new Error('Не удалось загрузить комментарии');
            }
        } catch (error) {
            console.error('Ошибка загрузки комментариев:', error);
            showToast('danger', 'Ошибка', 'Не удалось загрузить комментарии');
            commentsSection.style.display = 'none';
        }
    };

    // Показать форму комментария
    window.showCommentForm = function(postId) {
        const form = document.getElementById(`comment-form-${postId}`);
        if (form) {
            form.style.display = 'block';
            const textarea = document.getElementById(`comment-textarea-${postId}`);
            if (textarea) textarea.focus();
        }
    };

    // Ответить на комментарий
    window.replyToComment = function(postId, commentId, username) {
        const form = document.getElementById(`comment-form-${postId}`);
        const textarea = document.getElementById(`comment-textarea-${postId}`);
        const parentIdInput = document.getElementById(`comment-parent-id-${postId}`);
        const replyLabel = document.getElementById(`reply-to-label-${postId}`);

        if (form && textarea && parentIdInput && replyLabel) {
            form.style.display = 'block';
            parentIdInput.value = commentId;
            replyLabel.textContent = `Ответ для @${username}`;
            replyLabel.style.display = 'inline';
            textarea.focus();
        }
    };

    // Отправка комментария
    window.submitComment = async function(postId) {
        const textarea = document.getElementById(`comment-textarea-${postId}`);
        const parentIdInput = document.getElementById(`comment-parent-id-${postId}`);
        const content = textarea.value.trim();
        const parentId = parentIdInput ? parentIdInput.value : null;

        if (!content) {
            showToast('warning', 'Ошибка', 'Введите текст комментария');
            return;
        }

        try {
            const response = await fetch(`/wall/${postId}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    content: content,
                    parent_id: parentId || null
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('success', 'Успешно!', data.message);
                textarea.value = '';
                if (parentIdInput) parentIdInput.value = '';
                const replyLabel = document.getElementById(`reply-to-label-${postId}`);
                if (replyLabel) replyLabel.style.display = 'none';

                // Перезагружаем комментарии через AJAX вместо полной перезагрузки страницы
                const commentsSection = document.getElementById(`comments-${postId}`);
                commentsSection.dataset.loaded = 'false';
                await toggleComments(postId);

                // Обновляем счетчик комментариев
                const countSpan = document.querySelector(`#wall-post-${postId} .comments-count`);
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent) || 0;
                    countSpan.textContent = currentCount + 1;
                }
            } else {
                showToast('error', 'Ошибка', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Ошибка', 'Не удалось добавить комментарий');
        }
    };

    // Редактирование комментария
    window.editComment = function(commentId) {
        const commentElement = document.getElementById(`wall-comment-${commentId}`);
        const contentElement = commentElement.querySelector('.comment-content');
        const content = contentElement.innerHTML;

        document.getElementById('edit-comment-id').value = commentId;
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = content;
        document.getElementById('edit-comment-textarea').value = tempDiv.textContent || tempDiv.innerText || '';

        const modal = new bootstrap.Modal(document.getElementById('editWallCommentModal'));
        modal.show();
    };

    // Сохранение редактирования комментария
    document.getElementById('save-comment-edit').addEventListener('click', async function() {
        const commentId = document.getElementById('edit-comment-id').value;
        const content = document.getElementById('edit-comment-textarea').value.trim();

        if (!content) {
            showToast('warning', 'Ошибка', 'Текст комментария не может быть пустым');
            return;
        }

        try {
            const response = await fetch(`/wall/comments/${commentId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: '<p>' + content + '</p>' })
            });

            const data = await response.json();

            if (data.success) {
                showToast('success', 'Успешно!', data.message);
                bootstrap.Modal.getInstance(document.getElementById('editWallCommentModal')).hide();

                // Находим postId из commentId
                const commentElement = document.getElementById(`wall-comment-${commentId}`);
                const postElement = commentElement.closest('[id^="wall-post-"]');
                const postId = postElement.id.replace('wall-post-', '');

                // Перезагружаем комментарии через AJAX
                const commentsSection = document.getElementById(`comments-${postId}`);
                commentsSection.dataset.loaded = 'false';
                await toggleComments(postId);
            } else {
                showToast('error', 'Ошибка', data.message);
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось сохранить изменения');
        }
    });

    // Удаление комментария
    window.deleteComment = async function(commentId) {
        if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) {
            return;
        }

        try {
            const response = await fetch(`/wall/comments/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                showToast('success', 'Успешно!', data.message);

                // Находим postId из commentId
                const commentElement = document.getElementById(`wall-comment-${commentId}`);
                const postElement = commentElement.closest('[id^="wall-post-"]');
                const postId = postElement.id.replace('wall-post-', '');

                // Обновляем счетчик
                const countSpan = document.querySelector(`#wall-post-${postId} .comments-count`);
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent) || 0;
                    countSpan.textContent = Math.max(0, currentCount - 1);
                }

                // Удаляем элемент
                commentElement.remove();
            } else {
                showToast('error', 'Ошибка', data.message);
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось удалить комментарий');
        }
    };

    // Лайк комментария
    window.toggleCommentLike = async function(commentId) {
        try {
            const response = await fetch(`/wall/comments/${commentId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                const likeBtn = document.querySelector(`#wall-comment-${commentId} .comment-like-button`);
                const likeCount = document.querySelector(`#wall-comment-${commentId} .comment-like-count`);

                if (data.liked) {
                    likeBtn.classList.add('text-danger');
                    likeBtn.querySelector('i').classList.replace('bi-heart', 'bi-heart-fill');
                } else {
                    likeBtn.classList.remove('text-danger');
                    likeBtn.querySelector('i').classList.replace('bi-heart-fill', 'bi-heart');
                }

                likeCount.textContent = data.likes_count;
            }
        } catch (error) {
            showToast('error', 'Ошибка', 'Не удалось поставить лайк');
        }
    };
});
</script>
@endpush
