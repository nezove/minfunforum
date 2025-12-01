<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TopicGalleryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'image_path',
        'thumbnail_path', 
        'original_name',
        'description',
        'file_size',
        'width',
        'height',
        'sort_order'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'sort_order' => 'integer'
    ];

    /**
     * Связь с темой
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * URL полного изображения
     */
    public function getImageUrlAttribute()
    {
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * URL миниатюры
     */
    public function getThumbnailUrlAttribute()
    {
        return Storage::disk('public')->url($this->thumbnail_path);
    }

    /**
     * Человекочитаемый размер файла
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Удаление файлов изображения при удалении записи
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            // Удаляем оригинал
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
            
            // Удаляем миниатюру
            if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
                Storage::disk('public')->delete($image->thumbnail_path);
            }
        });
    }

    /**
     * Scope для сортировки по порядку
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}