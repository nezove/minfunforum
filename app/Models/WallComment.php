<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WallComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'wall_post_id',
        'parent_id',
        'user_id',
        'content',
        'edited_at',
        'edit_count'
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function wallPost()
    {
        return $this->belongsTo(WallPost::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function parent()
    {
        return $this->belongsTo(WallComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(WallComment::class, 'parent_id')->orderBy('created_at', 'asc');
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
               $user->id === $this->wallPost->wall_owner_id ||
               $user->isStaff();
    }
}
