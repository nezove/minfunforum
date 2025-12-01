<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'likeable_id', 'likeable_type'];

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likeable()
    {
        return $this->morphTo();
    }

    // Статические методы
    public static function toggle($userId, $likeable)
    {
        $like = self::where('user_id', $userId)
            ->where('likeable_id', $likeable->id)
            ->where('likeable_type', get_class($likeable))
            ->first();

        if ($like) {
            // Убираем лайк
            $like->delete();
            return ['liked' => false, 'count' => $likeable->likes()->count()];
        } else {
            // Добавляем лайк
            self::create([
                'user_id' => $userId,
                'likeable_id' => $likeable->id,
                'likeable_type' => get_class($likeable)
            ]);

            // Создаём уведомление
            if ($likeable instanceof Topic) {
                Notification::createNotification(
                    $likeable->user_id,
                    $userId,
                    'like_topic',
                    [
                        'topic_id' => $likeable->id,
                        'topic_title' => $likeable->title
                    ]
                );
            } elseif ($likeable instanceof Post) {
                Notification::createNotification(
                    $likeable->user_id,
                    $userId,
                    'like_post',
                    [
                        'post_id' => $likeable->id,
                        'topic_id' => $likeable->topic_id,
                        'post_content' => $likeable->content,
                        'topic_title' => $likeable->topic->title ?? ''
                    ]
                );
            }

            return ['liked' => true, 'count' => $likeable->likes()->count()];
        }
    }
}