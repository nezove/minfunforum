<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'type',
        'condition_type',
        'condition_value',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'condition_value' => 'integer',
        'display_order' => 'integer'
    ];

    /**
     * Пользователи, у которых есть эта награда
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('awarded_at', 'awarded_by');
    }

    /**
     * Проверить условие для пользователя
     */
    public function checkCondition(User $user): bool
    {
        if ($this->type !== 'auto') {
            return false;
        }

        return match($this->condition_type) {
            'posts_count' => $user->posts_count >= $this->condition_value,
            'topics_count' => $user->topics_count >= $this->condition_value,
            'days_active' => $user->created_at->diffInDays(now()) >= $this->condition_value,
            default => false
        };
    }

    /**
     * Получить URL иконки
     */
    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return asset('storage/' . $this->icon);
        }
        return asset('images/achievements/default.png');
    }
}
