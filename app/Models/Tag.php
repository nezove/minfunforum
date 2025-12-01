<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'color', 'seo_title', 
        'seo_description', 'seo_keywords', 'category_id', 
        'topics_count', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Отношения
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'topic_tag');
    }

    // Автоматически создаем slug при сохранении
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->name, $model->category_id);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = static::generateUniqueSlug($model->name, $model->category_id, $model->id);
            }
        });
    }

    // Генерация уникального slug
    private static function generateUniqueSlug($name, $categoryId, $ignoreId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
                     ->where('category_id', $categoryId)
                     ->when($ignoreId, function ($query, $ignoreId) {
                         return $query->where('id', '!=', $ignoreId);
                     })
                     ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    // Обновление счетчика тем
    public function updateTopicsCount()
    {
        $this->update(['topics_count' => $this->topics()->count()]);
    }

    // URL для страницы тега
    public function getUrlAttribute()
    {
        return route('tags.show', ['category' => $this->category->id, 'tag' => $this->slug]);
    }

    // Scope для активных тегов
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope для тегов с темами
    public function scopeWithTopics($query)
    {
        return $query->where('topics_count', '>', 0);
    }
}