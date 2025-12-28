<?php

namespace App\Http\Controllers;

use App\Models\WallPost;
use App\Models\WallComment;
use App\Models\User;
use App\Models\Notification;
use App\Services\ContentSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WallPostController extends Controller
{
    protected $contentSanitizer;

    public function __construct(ContentSanitizer $contentSanitizer)
    {
        $this->middleware('auth');
        $this->contentSanitizer = $contentSanitizer;
    }

    public function store(Request $request, $userId)
    {
        $user = Auth::user();
        $wallOwner = User::findOrFail($userId);

        if ($user->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы заблокированы и не можете публиковать на стене'
            ], 403);
        }

        if (!$wallOwner->allow_wall_posts && $user->id !== $wallOwner->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь запретил публикации на своей стене'
            ], 403);
        }

        $lockKey = 'wall_post_' . $user->id . '_' . $wallOwner->id;
        if (Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Пожалуйста, подождите 10 секунд перед публикацией следующего поста'
            ], 429);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $sanitizedContent = $this->contentSanitizer->sanitize($request->content);

        $wallPost = WallPost::create([
            'user_id' => $user->id,
            'wall_owner_id' => $wallOwner->id,
            'content' => $sanitizedContent,
        ]);

        Cache::put($lockKey, true, now()->addSeconds(10));

        // Создаем уведомление если это не своя стена
        if ($user->id !== $wallOwner->id) {
            $this->createWallPostNotification($wallPost, $wallOwner);
        }

        // Обрабатываем упоминания @username
        $this->processMentions($sanitizedContent, $wallPost, $user);

        $wallPost->load('user', 'likes');

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно опубликована',
            'wallPost' => $this->formatWallPostForJson($wallPost, $user)
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $wallPost = WallPost::findOrFail($id);

        if (!$wallPost->canEdit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на редактирование этого поста'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $sanitizedContent = $this->contentSanitizer->sanitize($request->content);

        $wallPost->update([
            'content' => $sanitizedContent,
            'edited_at' => now(),
            'edit_count' => $wallPost->edit_count + 1,
        ]);

        $wallPost->load('user', 'likes');

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно обновлена',
            'wallPost' => $this->formatWallPostForJson($wallPost, $user)
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $wallPost = WallPost::findOrFail($id);

        if (!$wallPost->canDelete($user)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на удаление этого поста'
            ], 403);
        }

        // Удаляем связанные уведомления
        Notification::where('data->wall_post_id', $wallPost->id)->delete();

        $wallPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно удалена'
        ]);
    }

    public function storeComment(Request $request, $wallPostId)
    {
        $user = Auth::user();
        $wallPost = WallPost::findOrFail($wallPostId);

        if ($user->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы заблокированы и не можете комментировать'
            ], 403);
        }

        $lockKey = 'wall_comment_' . $user->id . '_' . $wallPostId;
        if (Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Пожалуйста, подождите 10 секунд перед публикацией следующего комментария'
            ], 429);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:wall_comments,id'
        ]);

        $sanitizedContent = $this->contentSanitizer->sanitize($request->content);

        $comment = WallComment::create([
            'wall_post_id' => $wallPost->id,
            'parent_id' => $request->parent_id,
            'user_id' => $user->id,
            'content' => $sanitizedContent,
        ]);

        Cache::put($lockKey, true, now()->addSeconds(10));

        // Создаем уведомление автору поста если это не он сам
        if ($user->id !== $wallPost->user_id) {
            $this->createCommentNotification($comment, $wallPost);
        }

        // Если это ответ на комментарий, уведомляем автора родительского комментария
        if ($request->parent_id) {
            $parentComment = WallComment::find($request->parent_id);
            if ($parentComment && $parentComment->user_id !== $user->id) {
                $this->createReplyNotification($comment, $parentComment);
            }
        }

        // Обрабатываем упоминания
        $this->processMentionsInComment($sanitizedContent, $comment, $user, $wallPost);

        $comment->load('user', 'likes', 'parent.user');

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно добавлен',
            'comment' => $this->formatCommentForJson($comment, $user)
        ]);
    }

    public function updateComment(Request $request, $commentId)
    {
        $user = Auth::user();
        $comment = WallComment::with('wallPost')->findOrFail($commentId);

        if (!$comment->canEdit($user)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на редактирование этого комментария'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $sanitizedContent = $this->contentSanitizer->sanitize($request->content);

        $comment->update([
            'content' => $sanitizedContent,
            'edited_at' => now(),
            'edit_count' => $comment->edit_count + 1,
        ]);

        $comment->load('user', 'likes');

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно обновлен',
            'comment' => $this->formatCommentForJson($comment, $user)
        ]);
    }

    public function destroyComment($commentId)
    {
        $user = Auth::user();
        $comment = WallComment::with('wallPost')->findOrFail($commentId);

        if (!$comment->canDelete($user)) {
            return response()->json([
                'success' => false,
                'message' => 'У вас нет прав на удаление этого комментария'
            ], 403);
        }

        // Удаляем связанные уведомления
        Notification::where('data->wall_comment_id', $comment->id)->delete();

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Комментарий успешно удален'
        ]);
    }

    public function like($wallPostId)
    {
        $user = Auth::user();
        $wallPost = WallPost::findOrFail($wallPostId);

        if ($user->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы заблокированы'
            ], 403);
        }

        $existingLike = \App\Models\Like::where([
            'user_id' => $user->id,
            'likeable_type' => WallPost::class,
            'likeable_id' => $wallPost->id
        ])->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            \App\Models\Like::create([
                'user_id' => $user->id,
                'likeable_type' => WallPost::class,
                'likeable_id' => $wallPost->id
            ]);
            $liked = true;

            // Уведомление автору поста
            if ($user->id !== $wallPost->user_id) {
                $this->createLikeNotification($wallPost, $user);
            }
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $wallPost->likes()->count()
        ]);
    }

    public function likeComment($commentId)
    {
        $user = Auth::user();
        $comment = WallComment::findOrFail($commentId);

        if ($user->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы заблокированы'
            ], 403);
        }

        $existingLike = \App\Models\Like::where([
            'user_id' => $user->id,
            'likeable_type' => WallComment::class,
            'likeable_id' => $comment->id
        ])->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            \App\Models\Like::create([
                'user_id' => $user->id,
                'likeable_type' => WallComment::class,
                'likeable_id' => $comment->id
            ]);
            $liked = true;

            // Уведомление автору комментария
            if ($user->id !== $comment->user_id) {
                $this->createCommentLikeNotification($comment, $user);
            }
        }

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $comment->likes()->count()
        ]);
    }

    protected function createWallPostNotification($wallPost, $wallOwner)
    {
        $settings = $wallOwner->notificationSettings;
        if (!$settings || !$settings->notify_wall_post ?? true) {
            return;
        }

        $contentPreview = strip_tags($wallPost->content);
        $contentPreview = mb_substr($contentPreview, 0, 100);
        if (mb_strlen($wallPost->content) > 100) {
            $contentPreview .= '...';
        }

        Notification::createOrUpdate([
            'user_id' => $wallOwner->id,
            'from_user_id' => $wallPost->user_id,
            'type' => 'wall_post',
            'title' => '@' . $wallPost->user->username . ' опубликовал пост на вашей стене',
            'message' => '"' . $contentPreview . '"',
            'data' => [
                'wall_post_id' => $wallPost->id,
                'wall_owner_id' => $wallOwner->id,
            ],
            'direct_link' => route('profile.show', $wallOwner->id) . '#wall-post-' . $wallPost->id,
        ]);
    }

    protected function createCommentNotification($comment, $wallPost)
    {
        $settings = $wallPost->user->notificationSettings;
        if (!$settings || !$settings->notify_wall_comment ?? true) {
            return;
        }

        $contentPreview = strip_tags($comment->content);
        $contentPreview = mb_substr($contentPreview, 0, 100);
        if (mb_strlen($comment->content) > 100) {
            $contentPreview .= '...';
        }

        Notification::createOrUpdate([
            'user_id' => $wallPost->user_id,
            'from_user_id' => $comment->user_id,
            'type' => 'wall_comment',
            'title' => '@' . $comment->user->username . ' прокомментировал ваш пост на стене',
            'message' => '"' . $contentPreview . '"',
            'data' => [
                'wall_post_id' => $wallPost->id,
                'wall_comment_id' => $comment->id,
            ],
            'direct_link' => route('profile.show', $wallPost->wall_owner_id) . '#wall-post-' . $wallPost->id,
        ]);
    }

    protected function createLikeNotification($wallPost, $liker)
    {
        $settings = $wallPost->user->notificationSettings;
        if (!$settings || !$settings->notify_like_post ?? true) {
            return;
        }

        Notification::createOrUpdate([
            'user_id' => $wallPost->user_id,
            'from_user_id' => $liker->id,
            'type' => 'like_wall_post',
            'title' => '@' . $liker->username . ' оценил ваш пост на стене',
            'message' => '',
            'data' => [
                'wall_post_id' => $wallPost->id,
            ],
            'direct_link' => route('profile.show', $wallPost->wall_owner_id) . '#wall-post-' . $wallPost->id,
        ]);
    }

    protected function createCommentLikeNotification($comment, $liker)
    {
        $settings = $comment->user->notificationSettings;
        if (!$settings || !$settings->notify_like_post ?? true) {
            return;
        }

        Notification::createOrUpdate([
            'user_id' => $comment->user_id,
            'from_user_id' => $liker->id,
            'type' => 'like_wall_comment',
            'title' => '@' . $liker->username . ' оценил ваш комментарий',
            'message' => '',
            'data' => [
                'wall_comment_id' => $comment->id,
            ],
            'direct_link' => route('profile.show', $comment->wallPost->wall_owner_id) . '#wall-post-' . $comment->wall_post_id,
        ]);
    }

    /**
     * Загрузка комментариев поста через AJAX
     */
    public function loadComments($postId)
    {
        $wallPost = WallPost::with([
            'comments' => function($query) {
                $query->whereNull('parent_id')->with(['user', 'likes', 'replies.user', 'replies.likes', 'replies.parent.user']);
            }
        ])->findOrFail($postId);

        $commentsHtml = '';
        foreach ($wallPost->comments as $comment) {
            $commentsHtml .= view('profile.partials.wall-comment', [
                'comment' => $comment,
                'post' => $wallPost
            ])->render();
        }

        return response()->json([
            'success' => true,
            'html' => $commentsHtml,
            'count' => $wallPost->comments->count() + $wallPost->comments->sum(fn($c) => $c->replies->count())
        ]);
    }

    protected function createReplyNotification($reply, $parentComment)
    {
        $settings = $parentComment->user->notificationSettings;
        if (!$settings || !$settings->notify_wall_comment ?? true) {
            return;
        }

        $contentPreview = strip_tags($reply->content);
        $contentPreview = mb_substr($contentPreview, 0, 100);
        if (mb_strlen($reply->content) > 100) {
            $contentPreview .= '...';
        }

        Notification::createOrUpdate([
            'user_id' => $parentComment->user_id,
            'from_user_id' => $reply->user_id,
            'type' => 'wall_comment_reply',
            'title' => '@' . $reply->user->username . ' ответил на ваш комментарий',
            'message' => '"' . $contentPreview . '"',
            'data' => [
                'wall_post_id' => $reply->wall_post_id,
                'wall_comment_id' => $reply->id,
                'parent_comment_id' => $parentComment->id,
            ],
            'direct_link' => route('profile.show', $reply->wallPost->wall_owner_id) . '#wall-comment-' . $reply->id,
        ]);
    }

    protected function processMentions($content, $wallPost, $author)
    {
        preg_match_all('/@(\w+)/', $content, $matches);

        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);

            foreach ($usernames as $username) {
                $mentionedUser = User::where('username', $username)->first();

                if ($mentionedUser && $mentionedUser->id !== $author->id) {
                    $settings = $mentionedUser->notificationSettings;
                    if (!$settings || $settings->notify_mention ?? true) {
                        Notification::createOrUpdate([
                            'user_id' => $mentionedUser->id,
                            'from_user_id' => $author->id,
                            'type' => 'wall_mention',
                            'title' => '@' . $author->username . ' упомянул вас в записи на стене',
                            'message' => '',
                            'data' => [
                                'wall_post_id' => $wallPost->id,
                            ],
                            'direct_link' => route('profile.show', $wallPost->wall_owner_id) . '#wall-post-' . $wallPost->id,
                        ]);
                    }
                }
            }
        }
    }

    protected function processMentionsInComment($content, $comment, $author, $wallPost)
    {
        preg_match_all('/@(\w+)/', $content, $matches);

        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);

            foreach ($usernames as $username) {
                $mentionedUser = User::where('username', $username)->first();

                if ($mentionedUser && $mentionedUser->id !== $author->id) {
                    $settings = $mentionedUser->notificationSettings;
                    if (!$settings || $settings->notify_mention ?? true) {
                        Notification::createOrUpdate([
                            'user_id' => $mentionedUser->id,
                            'from_user_id' => $author->id,
                            'type' => 'wall_mention_comment',
                            'title' => '@' . $author->username . ' упомянул вас в комментарии',
                            'message' => '',
                            'data' => [
                                'wall_post_id' => $wallPost->id,
                                'wall_comment_id' => $comment->id,
                            ],
                            'direct_link' => route('profile.show', $wallPost->wall_owner_id) . '#wall-post-' . $wallPost->id,
                        ]);
                    }
                }
            }
        }
    }

    protected function formatWallPostForJson($wallPost, $currentUser)
    {
        return [
            'id' => $wallPost->id,
            'content' => $wallPost->content,
            'user' => [
                'id' => $wallPost->user->id,
                'username' => $wallPost->user->username,
                'name' => $wallPost->user->name,
                'avatar' => $wallPost->user->avatar,
            ],
            'likes_count' => $wallPost->likes->count(),
            'is_liked' => $wallPost->likes->where('user_id', $currentUser->id)->count() > 0,
            'can_edit' => $wallPost->canEdit($currentUser),
            'can_delete' => $wallPost->canDelete($currentUser),
            'created_at' => $wallPost->created_at->diffForHumans(),
            'edited_at' => $wallPost->edited_at ? $wallPost->edited_at->diffForHumans() : null,
            'edit_count' => $wallPost->edit_count,
        ];
    }

    protected function formatCommentForJson($comment, $currentUser)
    {
        $formatted = [
            'id' => $comment->id,
            'content' => $comment->content,
            'parent_id' => $comment->parent_id,
            'user' => [
                'id' => $comment->user->id,
                'username' => $comment->user->username,
                'name' => $comment->user->name,
                'avatar' => $comment->user->avatar,
            ],
            'likes_count' => $comment->likes->count(),
            'is_liked' => $comment->likes->where('user_id', $currentUser->id)->count() > 0,
            'can_edit' => $comment->canEdit($currentUser),
            'can_delete' => $comment->canDelete($currentUser),
            'created_at' => $comment->created_at->diffForHumans(),
            'edited_at' => $comment->edited_at ? $comment->edited_at->diffForHumans() : null,
            'edit_count' => $comment->edit_count,
        ];

        if ($comment->parent && $comment->parent->user) {
            $formatted['parent_user'] = [
                'id' => $comment->parent->user->id,
                'username' => $comment->parent->user->username,
                'name' => $comment->parent->user->name,
            ];
        }

        return $formatted;
    }

    // AJAX метод для загрузки постов (для ленивой загрузки)
    public function loadMore(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $page = $request->get('page', 1);

        $wallPosts = $user->wallPosts()
            ->with(['user', 'comments.user', 'comments.likes', 'likes'])
            ->latest()
            ->paginate(20, ['*'], 'page', $page);

        $currentUser = Auth::user();

        $formattedPosts = $wallPosts->map(function ($post) use ($currentUser) {
            return [
                'id' => $post->id,
                'content' => $post->content,
                'user' => [
                    'id' => $post->user->id,
                    'username' => $post->user->username,
                    'name' => $post->user->name,
                    'avatar' => $post->user->avatar,
                    'profile_url' => route('profile.show', $post->user->id)
                ],
                'likes_count' => $post->likes->count(),
                'is_liked' => $post->likes->where('user_id', $currentUser ? $currentUser->id : null)->count() > 0,
                'can_edit' => $currentUser ? $post->canEdit($currentUser) : false,
                'can_delete' => $currentUser ? $post->canDelete($currentUser) : false,
                'created_at' => $post->created_at->diffForHumans(),
                'edited_at' => $post->edited_at ? $post->edited_at->diffForHumans() : null,
                'edit_count' => $post->edit_count,
                'comments' => $post->comments->map(function ($comment) use ($currentUser) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => [
                            'id' => $comment->user->id,
                            'username' => $comment->user->username,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar,
                            'profile_url' => route('profile.show', $comment->user->id)
                        ],
                        'likes_count' => $comment->likes->count(),
                        'is_liked' => $comment->likes->where('user_id', $currentUser ? $currentUser->id : null)->count() > 0,
                        'can_edit' => $currentUser ? $comment->canEdit($currentUser) : false,
                        'can_delete' => $currentUser ? $comment->canDelete($currentUser) : false,
                        'created_at' => $comment->created_at->diffForHumans(),
                        'edited_at' => $comment->edited_at ? $comment->edited_at->diffForHumans() : null,
                        'edit_count' => $comment->edit_count,
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'posts' => $formattedPosts,
            'has_more' => $wallPosts->hasMorePages(),
            'current_page' => $wallPosts->currentPage(),
            'last_page' => $wallPosts->lastPage(),
        ]);
    }
}
