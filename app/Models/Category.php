<?php

// app/Models/Category.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'icon', 'sort_order', 'seo_title', 'seo_description', 'seo_keywords', 'allow_gallery'];

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function latestTopics()
    {
        return $this->hasMany(Topic::class)->latest();
    }

    public function getTopicsCountAttribute()
    {
        return $this->topics()->count();
    }

    public function getPostsCountAttribute()
    {
        return Post::whereHas('topic', function($query) {
            $query->where('category_id', $this->id);
        })->count();
    }
    // Отношение с тегами
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    // Активные теги
    public function activeTags()
    {
        return $this->hasMany(Tag::class)->active();
    }

    // Теги с темами (для отображения в категории)
    public function tagsWithTopics()
    {
        return $this->hasMany(Tag::class)->active()->withTopics();
    }
/**
 * Проверяет, разрешена ли галерея для данной категории
 */
public function allowsGallery()
{
    return $this->allow_gallery;
}

}
