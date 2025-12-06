<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'topic_id'];

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    // Статические методы
    public static function toggle($userId, $topicId)
    {
        $bookmark = self::where('user_id', $userId)
            ->where('topic_id', $topicId)
            ->first();

        if ($bookmark) {
            // Убираем из закладок
            $bookmark->delete();
            return ['bookmarked' => false];
        } else {
            // Добавляем в закладки
            self::create([
                'user_id' => $userId,
                'topic_id' => $topicId
            ]);
            return ['bookmarked' => true];
        }
    }
}