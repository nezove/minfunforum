<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Topic;
use App\Models\Post;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Применяем rate limiting для всех методов лайков
        $this->middleware('throttle:like')->only(['toggleTopic', 'togglePost']);
    }

    public function toggleTopic(Request $request, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            // КРИТИЧЕСКИ ВАЖНО: Проверка на бан с возвратом JSON
            if (!$user->canPerformActions()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ваш аккаунт ограничен. Вы не можете ставить лайки.',
                    'banned' => true,
                    'ban_type' => $user->getBanType(),
                    'ban_reason' => $user->ban_reason
                ], 403);
            }

            $userId = $user->id;
            
            // НОВАЯ ПРОВЕРКА: Автор не может лайкать свою собственную тему
            if ($topic->user_id === $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Лайкать собственную тему недопустимо.',
                    'self_like' => true
                ], 422);
            }
            
            // Дополнительная проверка на спам (не более 10 лайков в минуту)
            $cacheKey = "user_likes_per_minute_{$userId}";
            $likesThisMinute = Cache::get($cacheKey, 0);
            
            if ($likesThisMinute >= 10) {
                return response()->json([
                    'success' => false,
                    'error' => 'Слишком много действий. Подождите немного.',
                    'rate_limited' => true
                ], 429);
            }

            // Получаем или создаем/удаляем лайк
            $like = Like::where([
                'user_id' => $userId,
                'likeable_type' => Topic::class,
                'likeable_id' => $topic->id
            ])->first();

            if ($like) {
                // Убираем лайк
                $like->delete();
                $isLiked = false;
                $action = 'removed';
                            // УДАЛЯЕМ УВЕДОМЛЕНИЕ О ЛАЙКЕ
            \App\Models\Notification::where('type', 'like_topic')
                ->where('data->topic_id', $topic->id)
                ->where('from_user_id', $userId)
                ->delete();

            } else {
                // Добавляем лайк
                Like::create([
                    'user_id' => $userId,
                    'likeable_type' => Topic::class,
                    'likeable_id' => $topic->id
                ]);
                $isLiked = true;
                $action = 'added';
Cache::put($cacheKey, $likesThisMinute + 1, 60);
                // Создаем уведомление автору темы (проверка уже не нужна, так как автор не может лайкнуть сам себя)
                try {
                    Notification::createNotification(
                        $topic->user_id,
                        $userId,
                        'like_topic',
                        [
                            'topic_id' => $topic->id,
                            'topic_title' => $topic->title
                        ]
                    );
                } catch (\Exception $e) {
                    // Логируем ошибку создания уведомления, но не прерываем процесс лайка
                    \Log::error('Failed to create like notification: ' . $e->getMessage());
                }
            }


// Получаем обновленное количество лайков
$likesCount = Like::where([
    'likeable_type' => Topic::class,
    'likeable_id' => $topic->id
])->count();

            return response()->json([
                'success' => true,
                'liked' => $isLiked,
                'likes_count' => $likesCount,
                'action' => $action,
                'message' => $isLiked ? 'Лайк добавлен' : 'Лайк убран'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in toggleTopic: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обработке запроса. Попробуйте позже.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function togglePost(Request $request, Post $post)
    {
        try {
            $user = auth()->user();
            
            // КРИТИЧЕСКИ ВАЖНО: Проверка на бан с возвратом JSON
            if (!$user->canPerformActions()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ваш аккаунт ограничен. Вы не можете ставить лайки.',
                    'banned' => true,
                    'ban_type' => $user->getBanType(),
                    'ban_reason' => $user->ban_reason
                ], 403);
            }

            $userId = $user->id;
            
            // НОВАЯ ПРОВЕРКА: Автор не может лайкать свой собственный пост
            if ($post->user_id === $userId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Лайкать собственный пост недопустимо.',
                    'self_like' => true
                ], 422);
            }
            
            // Дополнительная проверка на спам
            $cacheKey = "user_likes_per_minute_{$userId}";
            $likesThisMinute = Cache::get($cacheKey, 0);
            
            if ($likesThisMinute >= 10) {
                return response()->json([
                    'success' => false,
                    'error' => 'Слишком много действий. Подождите немного.',
                    'rate_limited' => true
                ], 429);
            }

            // Получаем или создаем/удаляем лайк
            $like = Like::where([
                'user_id' => $userId,
                'likeable_type' => Post::class,
                'likeable_id' => $post->id
            ])->first();

            if ($like) {
                // Убираем лайк
                $like->delete();
                $isLiked = false;
                $action = 'removed';
            } else {
                // Добавляем лайк
                Like::create([
                    'user_id' => $userId,
                    'likeable_type' => Post::class,
                    'likeable_id' => $post->id
                ]);
                $isLiked = true;
                $action = 'added';
Cache::put($cacheKey, $likesThisMinute + 1, 60);
                // Создаем уведомление автору поста (проверка уже не нужна, так как автор не может лайкнуть сам себя)
                try {
                    // Получаем тему для уведомления
                    $topic = $post->topic;
                    $topicTitle = $topic ? $topic->title : 'Неизвестная тема';

                    Notification::createNotification(
                        $post->user_id,
                        $userId,
                        'like_post',
                        [
                            'post_id' => $post->id,
                            'topic_id' => $post->topic_id,
                            'topic_title' => $topicTitle
                        ]
                    );
                } catch (\Exception $e) {
                    // Логируем ошибку создания уведомления, но не прерываем процесс лайка
                    \Log::error('Failed to create like notification: ' . $e->getMessage());
                }
            }

            // Получаем обновленное количество лайков
$likesCount = Like::where([
    'likeable_type' => Post::class,
    'likeable_id' => $post->id
])->count();


            return response()->json([
                'success' => true,
                'liked' => $isLiked,
                'likes_count' => $likesCount,
                'action' => $action,
                'message' => $isLiked ? 'Лайк добавлен' : 'Лайк убран'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in togglePost: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Произошла ошибка при обработке запроса. Попробуйте позже.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Получить статус лайка для темы
     */
    public function getTopicLikeStatus(Topic $topic)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'error' => 'Необходимо авторизоваться'
            ], 401);
        }

        $userId = auth()->id();
        $isLiked = Like::where([
            'user_id' => $userId,
            'likeable_type' => Topic::class,
            'likeable_id' => $topic->id
        ])->exists();

        $likesCount = Like::where([
            'likeable_type' => Topic::class,
            'likeable_id' => $topic->id
        ])->count();

        return response()->json([
            'success' => true,
            'liked' => $isLiked,
            'likes_count' => $likesCount
        ]);
    }

    /**
     * Получить статус лайка для поста
     */
    public function getPostLikeStatus(Post $post)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'error' => 'Необходимо авторизоваться'
            ], 401);
        }

        $userId = auth()->id();
        $isLiked = Like::where([
            'user_id' => $userId,
            'likeable_type' => Post::class,
            'likeable_id' => $post->id
        ])->exists();

        $likesCount = Like::where([
            'likeable_type' => Post::class,
            'likeable_id' => $post->id
        ])->count();

        return response()->json([
            'success' => true,
            'liked' => $isLiked,
            'likes_count' => $likesCount
        ]);
    }
}