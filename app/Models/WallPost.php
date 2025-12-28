<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WallPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wall_owner_id',
        'content',
        'edited_at',
        'edit_count'
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallOwner()
    {
        return $this->belongsTo(User::class, 'wall_owner_id');
    }

    public function comments()
    {
        return $this->hasMany(WallComment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Получить общее количество комментариев (включая ответы)
     */
    public function getTotalCommentsCountAttribute()
    {
        $topLevelCount = $this->comments()->whereNull('parent_id')->count();
        $repliesCount = WallComment::whereIn('parent_id',
            $this->comments()->whereNull('parent_id')->pluck('id')
        )->count();

        return $topLevelCount + $repliesCount;
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function canEdit($user)
    {
        if (!$user) {
            return false;
        }

        if ($user->id !== $this->user_id && !$user->isStaff()) {
            return false;
        }

        $editTimeLimit = env('POST_EDIT_TIME_LIMIT', 24);
        $editDeadline = $this->created_at->addHours($editTimeLimit);

        return now()->lessThan($editDeadline) || $user->isStaff();
    }

    public function canDelete($user)
    {
        if (!$user) {
            return false;
        }

        return $user->id === $this->user_id ||
               $user->id === $this->wall_owner_id ||
               $user->isStaff();
    }
}
