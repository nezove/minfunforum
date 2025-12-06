<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmojiCategory extends Model
{
    use HasFactory;

    protected $table = 'emoji_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Отношение к смайликам
     */
    public function emojis()
    {
        return $this->hasMany(Emoji::class, 'category_id');
    }

    /**
     * Получить активные смайлики категории
     */
    public function activeEmojis()
    {
        return $this->hasMany(Emoji::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope для активных категорий
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
