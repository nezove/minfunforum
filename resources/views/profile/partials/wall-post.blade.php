<div class="card mb-3" id="wall-post-{{ $post->id }}">
    <div class="card-body">
        <!-- Заголовок поста -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-start">
                <!-- Аватар автора -->
                <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}"
                     class="rounded-circle me-3" width="48" height="48" style="object-fit: cover;">

                <div>
                    <!-- Имя автора и время -->
                    <h6 class="mb-0">
                        <a href="{{ route('profile.show', $post->user->id) }}" class="text-decoration-none">
                            {!! $post->user->styled_username !!}
                        </a>
                        @if($post->user->id !== $post->wall_owner_id)
                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            <a href="{{ route('profile.show', $post->wallOwner->id) }}" class="text-decoration-none">
                                {!! $post->wallOwner->styled_username !!}
                            </a>
                        @endif
                    </h6>
                    <small class="text-muted">
                        {{ $post->created_at->diffForHumans() }}
                        @if($post->edited_at)
                            <span class="ms-1" title="Отредактировано {{ $post->edited_at->format('d.m.Y H:i') }}">
                                <i class="bi bi-pencil-fill"></i> изменено
                            </span>
                        @endif
                    </small>
                </div>
            </div>

            <!-- Кнопка меню -->
            @auth
                @if($post->canEdit(auth()->user()) || $post->canDelete(auth()->user()))
                <div class="dropdown">
                    <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @if($post->canEdit(auth()->user()))
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)"
                               onclick="editWallPost({{ $post->id }})">
                                <i class="bi bi-pencil me-2"></i>Редактировать
                            </a>
                        </li>
                        @endif
                        @if($post->canDelete(auth()->user()))
                        <li>
                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                               onclick="deleteWallPost({{ $post->id }})">
                                <i class="bi bi-trash me-2"></i>Удалить
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif
            @endauth
        </div>

        <!-- Содержимое поста -->
        <div class="wall-post-content mb-3" data-post-id="{{ $post->id }}">
            {!! $post->content !!}
        </div>

        <!-- Панель действий -->
        <div class="d-flex align-items-center border-top pt-3">
            @auth
                @if(!auth()->user()->isBanned())
                <!-- Лайк -->
                <button class="btn btn-sm btn-link text-decoration-none like-button {{ $post->likes->where('user_id', auth()->id())->count() > 0 ? 'text-danger' : 'text-muted' }}"
                        onclick="toggleWallPostLike({{ $post->id }})">
                    <i class="bi bi-heart{{ $post->likes->where('user_id', auth()->id())->count() > 0 ? '-fill' : '' }} me-1"></i>
                    <span class="like-count">{{ $post->likes->count() }}</span>
                </button>

                <!-- Комментарий -->
                <button class="btn btn-sm btn-link text-muted text-decoration-none ms-3"
                        onclick="toggleComments({{ $post->id }})">
                    <i class="bi bi-chat me-1"></i>
                    <span class="comments-count">{{ $post->comments_count ?? 0 }}</span>
                </button>
                @else
                <span class="text-muted small">
                    <i class="bi bi-heart me-1"></i>
                    <span class="like-count">{{ $post->likes->count() }}</span>
                </span>
                <span class="text-muted small ms-3">
                    <i class="bi bi-chat me-1"></i>
                    {{ $post->comments_count ?? 0 }}
                </span>
                @endif
            @else
                <span class="text-muted small">
                    <i class="bi bi-heart me-1"></i>
                    <span class="like-count">{{ $post->likes->count() }}</span>
                </span>
                <span class="text-muted small ms-3">
                    <i class="bi bi-chat me-1"></i>
                    {{ $post->comments_count ?? 0 }}
                </span>
            @endauth
        </div>

        <!-- Комментарии (скрыты по умолчанию, загружаются через AJAX) -->
        <div class="comments-section mt-3 pt-3 border-top" id="comments-{{ $post->id }}" style="display: none;" data-loaded="false">
            <div class="comments-loading text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
            </div>
            <div class="comments-container"></div>
        </div>

        <!-- Форма добавления комментария -->
        @auth
            @if(!auth()->user()->isBanned())
            <div class="comment-form mt-3 border-top pt-3" id="comment-form-{{ $post->id }}" style="display: none;">
                <div class="d-flex align-items-start">
                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                         class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">

                    <div class="flex-grow-1">
                        <textarea class="form-control form-control-sm mb-2"
                                  id="comment-textarea-{{ $post->id }}"
                                  rows="2"
                                  placeholder="Напишите комментарий..."></textarea>
                        <input type="hidden" id="comment-parent-id-{{ $post->id }}" value="">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="reply-to-label small text-muted" id="reply-to-label-{{ $post->id }}" style="display: none;"></span>
                            <button class="btn btn-sm btn-primary" onclick="submitComment({{ $post->id }})">
                                <i class="bi bi-send me-1"></i>Отправить
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endauth
    </div>
</div>
