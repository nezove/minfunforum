<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Emoji extends Model
{
    use HasFactory;

    protected $table = 'emojis';

    protected $fillable = [
        'category_id',
        'name',
        'file_path',
        'keywords',
        'file_type',
        'width',
        'height',
        'sort_order',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * Отношение к категории
     */
    public function category()
    {
        return $this->belongsTo(EmojiCategory::class, 'category_id');
    }

    /**
     * Получить URL файла
     */
    public function getFileUrlAttribute()
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Получить массив ключевых слов
     */
    public function getKeywordsArrayAttribute()
    {
        return array_map('trim', explode(',', $this->keywords));
    }

    /**
     * Scope для активных смайликов
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

    /**
     * Scope для популярных смайликов
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Поиск по ключевым словам
     */
    public function scopeSearchByKeyword($query, $keyword)
    {
        return $query->where('keywords', 'like', '%' . $keyword . '%');
    }

    /**
     * Увеличить счетчик использования
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
