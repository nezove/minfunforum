<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id', 'from_user_id', 'type', 'title', 'message', 'data', 'is_read', 'read_at', 'direct_link'
];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    // Скоупы
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    // Методы
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * НОВОЕ: Получить иконку для типа уведомления
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            'reply', 'reply_to_post' => 'bi-reply',
            'mention', 'mention_topic' => 'bi-at',
            'like_topic', 'like_post' => 'bi-heart-fill',
            'topic_deleted', 'post_deleted' => 'bi-trash',
            'topic_moved' => 'bi-arrow-right-square',
            default => 'bi-bell'
        };
    }

/**
 * Получить прямую ссылку на уведомление
 */
public function getDirectLinkAttribute()
{
    // Если есть сохранённая прямая ссылка, используем её
    if (isset($this->attributes['direct_link']) && !empty($this->attributes['direct_link'])) {
        return $this->attributes['direct_link'];
    }

    $data = $this->data;
    
    try {
        // Специальные типы уведомлений
        if (in_array($this->type, ['temporary_ban', 'permanent_ban'])) {
            return route('banned');
        }

        // Если есть конкретный пост
        if (isset($data['post_id'])) {
            $post = Post::with('topic')->find($data['post_id']);
            if ($post) {
                // Вычисляем правильную страницу для поста
                $postsPerPage = 20;
                
                $postsBeforeThis = $post->topic->posts()
                    ->where('created_at', '<', $post->created_at)
                    ->orWhere(function($query) use ($post) {
                        $query->where('created_at', '=', $post->created_at)
                              ->where('id', '<', $post->id);
                    })
                    ->count();

                $page = ceil(($postsBeforeThis + 1) / $postsPerPage);
                $url = route('topics.show', $post->topic_id);
                
                if ($page > 1) {
                    $url .= '?page=' . $page;
                }
                
                return $url . '#post-' . $post->id;
            }
        }
        
        // Если есть только тема
        if (isset($data['topic_id'])) {
            return route('topics.show', $data['topic_id']);
        }
    } catch (\Exception $e) {
        \Log::error("Error generating direct link for notification {$this->id}: " . $e->getMessage());
    }
    
    // Если ничего не найдено или произошла ошибка, возвращаем главную страницу форума
    return route('forum.index');
}

    /**
     * Создать или обновить уведомление
     */
    public static function createOrUpdate(array $attributes)
    {
        // Проверяем, не создаём ли мы уведомление самому себе
        if ($attributes['user_id'] == $attributes['from_user_id']) {
            return null;
        }

        return self::create($attributes);
    }

    // Статические методы для создания уведомлений
    public static function createNotification($userId, $fromUserId, $type, $data = [])
    {
        // Проверяем, не создаём ли мы уведомление самому себе
        if ($userId == $fromUserId) {
            return null;
        }

        // Для некоторых типов уведомлений проверяем дубликаты
        if (in_array($type, ['reply', 'like_topic', 'like_post'])) {
            $existing = self::where('user_id', $userId)
                ->where('type', $type)
                ->where('data->topic_id', $data['topic_id'] ?? null)
                ->where('created_at', '>=', now()->subHour()) // уменьшили до часа для более актуальных уведомлений
                ->first();

            if ($existing) {
                // Обновляем существующее уведомление
                $existingData = $existing->data;
                $existingData['count'] = ($existingData['count'] ?? 1) + 1;
                $existingData['latest_user_id'] = $fromUserId;
                $existingData['latest_user_name'] = User::find($fromUserId)->username;
                
                // Обновляем post_id на самый последний
                if (isset($data['post_id'])) {
                    $existingData['post_id'] = $data['post_id'];
                }
                
                $existing->update([
                    'from_user_id' => $fromUserId,
                    'data' => $existingData,
                    'message' => self::generateMessage($type, $existingData),
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => now()
                ]);

                return $existing;
            }
        }

        // Создаём новое уведомление
        $data['count'] = 1;
        $data['latest_user_id'] = $fromUserId;
        $data['latest_user_name'] = User::find($fromUserId)->username;

        return self::create([
            'user_id' => $userId,
            'from_user_id' => $fromUserId,
            'type' => $type,
            'title' => self::generateTitle($type),
            'message' => self::generateMessage($type, $data),
            'data' => $data
        ]);
    }

    /**
     * НОВОЕ: Создать уведомление об удалении темы модератором
     */
    public static function createTopicDeletedNotification($userId, $moderatorId, $topicTitle, $reason)
    {
        return self::create([
            'user_id' => $userId,
            'from_user_id' => $moderatorId,
            'type' => 'topic_deleted',
            'title' => 'Тема удалена модератором',
            'message' => "Ваша тема \"" . Str::limit($topicTitle, 30) . "\" удалена модератором по причине \"$reason\"",
            'data' => [
                'topic_title' => $topicTitle,
                'reason' => $reason,
                'moderator_id' => $moderatorId,
                'moderator_name' => User::find($moderatorId)->username ?? 'Модератор'
            ]
        ]);
    }

    /**
     * НОВОЕ: Создать уведомление об удалении поста модератором
     */
    public static function createPostDeletedNotification($userId, $moderatorId, $postContent, $reason, $topicId)
    {
        return self::create([
            'user_id' => $userId,
            'from_user_id' => $moderatorId,
            'type' => 'post_deleted',
            'title' => 'Пост удален модератором',
            'message' => "Ваш пост \"" . Str::limit(strip_tags($postContent), 30) . "\" удален модератором по причине \"$reason\"",
            'data' => [
                'post_content' => $postContent,
                'topic_id' => $topicId,
                'reason' => $reason,
                'moderator_id' => $moderatorId,
                'moderator_name' => User::find($moderatorId)->username ?? 'Модератор'
            ]
        ]);
    }

    /**
     * НОВОЕ: Создать уведомление о перемещении темы
     */
    public static function createTopicMovedNotification($userId, $moderatorId, $topicTitle, $oldCategoryName, $newCategoryName, $topicId, $reason = null)
    {
        $moderatorName = User::find($moderatorId)->username ?? 'Модератор';
        
        // Формируем сообщение в зависимости от наличия причины
        if ($reason) {
            $message = "Ваша тема \"" . Str::limit($topicTitle, 25) . "\" перемещена в раздел \"$newCategoryName\". Причина: \"$reason\"";
        } else {
            $message = "Ваша тема \"" . Str::limit($topicTitle, 25) . "\" перемещена в раздел \"$newCategoryName\"";
        }

        return self::create([
            'user_id' => $userId,
            'from_user_id' => $moderatorId,
            'type' => 'topic_moved',
            'title' => 'Тема перемещена',
            'message' => $message,
            'data' => [
                'topic_id' => $topicId,
                'topic_title' => $topicTitle,
                'old_category' => $oldCategoryName,
                'new_category' => $newCategoryName,
                'reason' => $reason,
                'moderator_id' => $moderatorId,
                'moderator_name' => $moderatorName
            ]
        ]);
    }

    private static function generateTitle($type)
    {
        return match($type) {
            'reply' => 'Новый ответ в теме',
            'reply_to_post' => 'Ответ на ваш пост',
            'mention' => 'Вас упомянули',
            'mention_topic' => 'Вас упомянули в теме',
            'like_topic' => 'Лайк темы',
            'like_post' => 'Лайк ответа',
            'topic_deleted' => 'Тема удалена модератором',
            'post_deleted' => 'Пост удален модератором',
            'topic_moved' => 'Тема перемещена',
            default => 'Уведомление'
        };
    }

    private static function generateMessage($type, $data)
    {
        $count = $data['count'] ?? 1;
        $userName = $data['latest_user_name'] ?? '';
        $topicTitle = Str::limit($data['topic_title'] ?? '', 30);
        $postContent = Str::limit(strip_tags($data['post_content'] ?? ''), 20);
        $mentionedIn = $data['mentioned_in'] ?? 'post';

        return match($type) {
            'reply' => $count > 1
                ? "$userName и ещё " . ($count - 1) . " пользователей ответили в вашей теме \"$topicTitle\""
                : "$userName добавил ответ в вашу тему \"$topicTitle\"",
            
            'reply_to_post' => $count > 1 
                ? "$userName и ещё " . ($count - 1) . " пользователей ответили на ваш пост в теме \"$topicTitle\""
                : "$userName ответил на ваш пост в теме \"$topicTitle\"",
            
            'mention' => "$userName упомянул вас в " . ($mentionedIn === 'topic' ? 'теме' : 'посте') . " \"$topicTitle\"",
            
            'mention_topic' => "$userName упомянул вас в теме \"$topicTitle\"",
            
            'like_topic' => $count > 1
                ? "$userName и ещё " . ($count - 1) . " пользователей лайкнули вашу тему \"$topicTitle\""
                : "$userName лайкнул вашу тему \"$topicTitle\"",
            
            'like_post' => $count > 1
                ? "$userName и ещё " . ($count - 1) . " пользователей лайкнули ваш пост в теме \"$topicTitle\""
                : "$userName лайкнул ваш пост в теме \"$topicTitle\"",
            
            default => 'У вас новое уведомление'
        };
    }
/**
 * Создать уведомление о временной блокировке аккаунта
 */
public static function createTemporaryBanNotification($userId, $moderatorId, $reason, $bannedUntil)
{
    return self::create([
        'user_id' => $userId,
        'from_user_id' => $moderatorId,
        'type' => 'temporary_ban',
        'title' => 'Аккаунт временно ограничен',
        'message' => "Ваш аккаунт временно ограничен до " . $bannedUntil->format('d.m.Y H:i') . ". Причина: " . $reason,
        'direct_link' => route('banned'), // Явно указываем ссылку
        'data' => [
            'reason' => $reason,
            'banned_until' => $bannedUntil->toISOString(),
            'moderator_id' => $moderatorId,
            'moderator_name' => User::find($moderatorId)->username ?? 'Модератор'
        ]
    ]);
}

/**
 * Создать уведомление о постоянной блокировке аккаунта
 */
public static function createPermanentBanNotification($userId, $moderatorId, $reason)
{
    return self::create([
        'user_id' => $userId,
        'from_user_id' => $moderatorId,
        'type' => 'permanent_ban',
        'title' => 'Аккаунт заблокирован навсегда',
        'message' => "Ваш аккаунт заблокирован навсегда. Причина: " . $reason,
        'direct_link' => route('banned'), // Явно указываем ссылку
        'data' => [
            'reason' => $reason,
            'moderator_id' => $moderatorId,
            'moderator_name' => User::find($moderatorId)->username ?? 'Модератор'
        ]
    ]);
}
}