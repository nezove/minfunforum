<div class="comment mb-3 {{ isset($isReply) && $isReply ? 'ms-5' : '' }}" id="wall-comment-{{ $comment->id }}">
    <div class="d-flex align-items-start">
        <!-- Аватар комментатора -->
        <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}"
             class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">

        <div class="flex-grow-1">
            <!-- Блок комментария -->
            <div class="bg-light rounded p-2 mb-1">
                <h6 class="mb-1 small">
                    <a href="{{ route('profile.show', $comment->user->id) }}" class="text-decoration-none">
                        {!! $comment->user->styled_username !!}
                    </a>
                    @if($comment->parent_id && $comment->parent)
                        <i class="bi bi-arrow-return-right mx-1 text-muted"></i>
                        <a href="{{ route('profile.show', $comment->parent->user->id) }}" class="text-decoration-none text-muted">
                            {{ '@' . $comment->parent->user->username }}
                        </a>
                    @endif
                </h6>
                <div class="comment-content small">
                    {!! $comment->content !!}
                </div>
            </div>

            <!-- Действия с комментарием -->
            <div class="d-flex align-items-center small">
                <small class="text-muted me-3">
                    {{ $comment->created_at->diffForHumans() }}
                    @if($comment->edited_at)
                        <span class="ms-1" title="Отредактировано {{ $comment->edited_at->format('d.m.Y H:i') }}">
                            <i class="bi bi-pencil-fill"></i>
                        </span>
                    @endif
                </small>

                @auth
                    @if(!auth()->user()->isBanned())
                    <button class="btn btn-sm btn-link text-decoration-none p-0 me-2 comment-like-button {{ $comment->likes->where('user_id', auth()->id())->count() > 0 ? 'text-danger' : 'text-muted' }}"
                            onclick="toggleCommentLike({{ $comment->id }})">
                        <i class="bi bi-heart{{ $comment->likes->where('user_id', auth()->id())->count() > 0 ? '-fill' : '' }} me-1"></i>
                        <span class="comment-like-count">{{ $comment->likes->count() }}</span>
                    </button>

                    <button class="btn btn-sm btn-link text-decoration-none p-0 text-muted me-2"
                            onclick="replyToComment({{ $post->id }}, {{ $comment->id }}, '{{ $comment->user->username }}')">
                        <i class="bi bi-reply me-1"></i>Ответить
                    </button>
                    @else
                    <span class="text-muted small me-2">
                        <i class="bi bi-heart me-1"></i>
                        <span class="comment-like-count">{{ $comment->likes->count() }}</span>
                    </span>
                    @endif
                @else
                    <span class="text-muted small me-2">
                        <i class="bi bi-heart me-1"></i>
                        <span class="comment-like-count">{{ $comment->likes->count() }}</span>
                    </span>
                @endauth

                @auth
                    @if($comment->canEdit(auth()->user()) || $comment->canDelete(auth()->user()))
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if($comment->canEdit(auth()->user()))
                            <li>
                                <a class="dropdown-item small" href="javascript:void(0)"
                                   onclick="editComment({{ $comment->id }})">
                                    <i class="bi bi-pencil me-2"></i>Редактировать
                                </a>
                            </li>
                            @endif
                            @if($comment->canDelete(auth()->user()))
                            <li>
                                <a class="dropdown-item small text-danger" href="javascript:void(0)"
                                   onclick="deleteComment({{ $comment->id }})">
                                    <i class="bi bi-trash me-2"></i>Удалить
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <!-- Ответы на комментарий -->
    @if($comment->replies && $comment->replies->count() > 0)
        <div class="replies mt-2">
            @foreach($comment->replies as $reply)
                @include('profile.partials.wall-comment', ['comment' => $reply, 'post' => $post, 'isReply' => true])
            @endforeach
        </div>
    @endif
</div>
