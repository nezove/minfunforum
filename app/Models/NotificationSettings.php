<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_reply',
        'notify_reply_to_post',
        'notify_mention',
        'notify_mention_topic',
        'notify_like_topic',
        'notify_like_post',
        'notify_topic_deleted',
        'notify_post_deleted',
        'notify_topic_moved',
        'notify_bans',
    ];

    protected $casts = [
        'notify_reply' => 'boolean',
        'notify_reply_to_post' => 'boolean',
        'notify_mention' => 'boolean',
        'notify_mention_topic' => 'boolean',
        'notify_like_topic' => 'boolean',
        'notify_like_post' => 'boolean',
        'notify_topic_deleted' => 'boolean',
        'notify_post_deleted' => 'boolean',
        'notify_topic_moved' => 'boolean',
        'notify_bans' => 'boolean',
    ];

    /**
     * Пользователь, которому принадлежат настройки
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить настройки для пользователя или создать дефолтные
     */
    public static function getForUser($userId)
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'notify_reply' => true,
                'notify_reply_to_post' => true,
                'notify_mention' => true,
                'notify_mention_topic' => true,
                'notify_like_topic' => true,
                'notify_like_post' => true,
                'notify_topic_deleted' => true,
                'notify_post_deleted' => true,
                'notify_topic_moved' => true,
                'notify_bans' => true,
            ]
        );
    }

    /**
     * Проверить, включено ли уведомление определенного типа
     */
    public function isEnabled(string $notificationType): bool
    {
        $field = 'notify_' . $notificationType;
        return $this->$field ?? true;
    }
}
