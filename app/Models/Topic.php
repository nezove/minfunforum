<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\MentionHelper;
use Illuminate\Support\Facades\Storage;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'last_activity_at',
        'replies_count',
        'likes_count',
        'views',
        'is_closed',
        'tags',
        'pin_type',
        'edited_at',
        'edit_count'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_closed' => 'boolean',
        'pin_type' => 'string',
        'edited_at' => 'datetime',
    ];

    // === СВЯЗИ ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function files()
    {
        return $this->hasMany(TopicFile::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function lastPost()
    {
        return $this->hasOne(Post::class)->latest();
    }

    // Отношение с тегами
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'topic_tag');
    }

    // Синхронизация тегов с обновлением счетчиков
    public function syncTags(array $tagIds)
    {
        // Получаем старые теги для обновления счетчика
        $oldTags = $this->tags()->pluck('tags.id')->toArray();
        
        // Синхронизируем теги
        $this->tags()->sync($tagIds);
        
        // Обновляем счетчики для старых тегов
        foreach ($oldTags as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                $tag->updateTopicsCount();
            }
        }
        
        // Обновляем счетчики для новых тегов
        foreach ($tagIds as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                $tag->updateTopicsCount();
            }
        }
    }

    // === МЕТОДЫ ДЛЯ МОДЕРАЦИИ ===

    /**
     * Проверка, закрыта ли тема
     */
    public function isClosed(): bool
    {
        return $this->is_closed ?? false;
    }

    /**
     * Проверка, может ли пользователь отвечать в теме
     */
    public function canReply($user = null): bool
    {
        if ($this->is_closed) {
            return $user && $user->isStaff();
        }
        return true;
    }

    /**
     * Проверка, понравилась ли тема пользователю
     */
    public function isLikedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // Получаем ID пользователя (если передан объект или ID)
        $userId = is_object($user) ? $user->id : $user;

        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Проверка, добавил ли пользователь тему в закладки
     */
    public function isBookmarkedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // Получаем ID пользователя (если передан объект или ID)
        $userId = is_object($user) ? $user->id : $user;

        return $this->bookmarks()->where('user_id', $userId)->exists();
    }

    /**
     * Проверка, закреплена ли тема
     */
    public function isPinned(): bool
    {
        return $this->pin_type !== 'none';
    }

    /**
     * Проверка, закреплена ли тема глобально
     */
    public function isPinnedGlobally(): bool
    {
        return $this->pin_type === 'global';
    }

    /**
     * Проверка, закреплена ли тема в категории
     */
    public function isPinnedInCategory(): bool
    {
        return $this->pin_type === 'category';
    }

    /**
     * Закрепить тему глобально
     */
    public function pinGlobally(): void
    {
        $this->update(['pin_type' => 'global']);
    }

    /**
     * Закрепить тему в категории
     */
    public function pinInCategory(): void
    {
        $this->update(['pin_type' => 'category']);
    }

    /**
     * Открепить тему
     */
    public function unpin(): void
    {
        $this->update(['pin_type' => 'none']);
    }

    /**
     * Получить текст типа закрепления
     */
    public function getPinTypeTextAttribute(): string
    {
        return match($this->pin_type) {
            'global' => 'Закреплено глобально',
            'category' => 'Закреплено в категории',
            default => 'Не закреплено'
        };
    }

    /**
     * Получить иконку закрепления
     */
    public function shouldShowPinIconOnHomePage(): string
    {
        return match($this->pin_type) {
            'global' => 'bi-pin-fill text-danger',
            'category' => 'bi-pin-angle-fill text-warning',
            default => ''
        };
    }

    /**
     * Проверяет, можно ли редактировать тему
     */
    public function canEdit($user = null): bool
    {
        $user = $user ?: auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Админы и модераторы могут редактировать всегда
        if ($user->canModerate()) {
            return true;
        }
        
        // Проверяем, что это автор темы
        if ($this->user_id !== $user->id) {
            return false;
        }
        
        // Проверяем временное ограничение
        $timeLimit = env('TOPIC_EDIT_TIME_LIMIT', 1440); // минуты (24 часа по умолчанию)
        $timeLimitExpired = $this->created_at->addMinutes($timeLimit)->isPast();
        
        return !$timeLimitExpired;
    }

    /**
     * Проверяет, можно ли удалять тему
     */
    public function canDelete($user = null): bool
    {
        $user = $user ?: auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Админы и модераторы могут удалять всегда
        if ($user->canModerate()) {
            return true;
        }
        
        // Проверяем, что это автор темы
        if ($this->user_id !== $user->id) {
            return false;
        }
        
        // Проверяем временное ограничение (такое же как для редактирования)
        $timeLimit = env('TOPIC_EDIT_TIME_LIMIT', 1440); // минуты
        $timeLimitExpired = $this->created_at->addMinutes($timeLimit)->isPast();
        
        return !$timeLimitExpired;
    }

    /**
     * Возвращает текст о редактировании
     */
    public function getEditedTextAttribute()
    {
        if (!$this->edited_at || $this->edit_count == 0) {
            return null;
        }
        
        return 'изменено ' . $this->edited_at->diffForHumans() . 
               ($this->edit_count > 1 ? " ({$this->edit_count} раз)" : '');
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    // === СКОУПЫ ===

    /**
     * Скоуп для глобально закрепленных тем
     */
    public function scopePinnedGlobally($query)
    {
        return $query->where('pin_type', 'global');
    }

    /**
     * Скоуп для закрепленных в категории тем
     */
    public function scopePinnedInCategory($query)
    {
        return $query->where('pin_type', 'category');
    }

    /**
     * Скоуп для незакрепленных тем
     */
    public function scopeNotPinned($query)
    {
        return $query->where('pin_type', 'none');
    }

    /**
     * Скоуп для получения открытых тем
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Скоуп для получения закрытых тем
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Скоуп для поиска по заголовку
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
    }

    /**
     * Скоуп для получения популярных тем
     */
    public function scopePopular($query)
    {
        return $query->orderBy('likes_count', 'desc')
                    ->orderBy('replies_count', 'desc');
    }

    /**
     * Скоуп для получения активных тем
     */
    public function scopeActive($query)
    {
        return $query->orderBy('last_activity_at', 'desc');
    }

    // === СОБЫТИЯ МОДЕЛИ ===

    protected static function booted()
    {
        // При создании темы устанавливаем last_activity_at
        static::creating(function ($topic) {
            if (!$topic->last_activity_at) {
                $topic->last_activity_at = now();
            }
        });
    static::deleting(function ($topic) {
        // Удаляем изображения галереи
        foreach ($topic->galleryImages as $image) {
            $image->delete(); // Вызовет boot() метод TopicGalleryImage
        }
        
        // Удаляем обычные файлы
        foreach ($topic->files as $file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        }
        
        // Удаляем изображения из контента
        static::cleanupContentImages($topic->content);
    });

        // При удалении темы удаляем связанные уведомления
        static::deleting(function ($topic) {
            \Log::info('Deleting topic with files cleanup', [
                'topic_id' => $topic->id,
                'topic_title' => $topic->title
            ]);

            try {
                // 1. Удаляем изображения из контента темы
                self::deleteContentImages($topic->content);

                // 2. Удаляем прикрепленные файлы темы
                foreach ($topic->files as $file) {
                    self::deleteTopicFile($file);
                }

                // 3. Обрабатываем все посты темы
                $posts = $topic->posts()->with('files')->get();
                foreach ($posts as $post) {
                    // Удаляем изображения из контента каждого поста
                    self::deleteContentImages($post->content);
                    
                    // Удаляем прикрепленные файлы каждого поста
                    foreach ($post->files as $file) {
                        self::deletePostFile($file);
                    }
                }

                // 4. Удаляем связанные записи
                $topic->likes()->delete();
                $topic->bookmarks()->delete();
                
                // 5. Удаляем уведомления
                \App\Models\Notification::where('data->topic_id', $topic->id)->delete();

                // 6. Обновляем счетчики тегов
                foreach ($topic->tags as $tag) {
                    $tag->updateTopicsCount();
                }

                \Log::info('Topic files cleanup completed', ['topic_id' => $topic->id]);

            } catch (\Exception $e) {
                \Log::error('Error during topic deletion cleanup', [
                    'topic_id' => $topic->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }

    /**
     * Удаление изображений из HTML контента
     */
    private static function deleteContentImages($content)
    {
        if (empty($content)) {
            return;
        }

        // Ищем все изображения в контенте (как с полными URL, так и относительные пути)
        preg_match_all('/<img[^>]*src=["\']([^"\']*(?:\/storage\/images\/|images\/)[^"\']*)["\'][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $imageUrl) {
                // Нормализуем путь - убираем домен и /storage/
                $imagePath = $imageUrl;
                
                // Если это полный URL, извлекаем путь
                if (strpos($imageUrl, 'http') === 0) {
                    $parsedUrl = parse_url($imageUrl);
                    $imagePath = $parsedUrl['path'] ?? '';
                }
                
                // Убираем /storage/ из начала пути
                $imagePath = preg_replace('#^/storage/#', '', $imagePath);
                
                try {
                    // Удаляем основной файл
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        \Log::info('Deleted content image', ['path' => $imagePath]);
                    }
                    
                    // Удаляем превью (если это оригинал)
                    if (strpos($imagePath, '_original.jpg') !== false) {
                        $thumbnailPath = str_replace('_original.jpg', '_thumb.jpg', $imagePath);
                        $thumbnailPath = str_replace('/originals/', '/thumbnails/', $thumbnailPath);
                        
                        if (Storage::disk('public')->exists($thumbnailPath)) {
                            Storage::disk('public')->delete($thumbnailPath);
                            \Log::info('Deleted thumbnail', ['path' => $thumbnailPath]);
                        }
                    }
                    
                    // Удаляем оригинал (если это превью)
                    if (strpos($imagePath, '_thumb.jpg') !== false) {
                        $originalPath = str_replace('_thumb.jpg', '_original.jpg', $imagePath);
                        $originalPath = str_replace('/thumbnails/', '/originals/', $originalPath);
                        
                        if (Storage::disk('public')->exists($originalPath)) {
                            Storage::disk('public')->delete($originalPath);
                            \Log::info('Deleted original image', ['path' => $originalPath]);
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Error deleting content image', [
                        'path' => $imagePath,
                        'url' => $imageUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Удаление файла темы
     */
    private static function deleteTopicFile($file)
    {
        try {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
                \Log::info('Deleted topic file', [
                    'file_id' => $file->id,
                    'path' => $file->file_path,
                    'name' => $file->original_name
                ]);
            }
            
            // Удаляем запись из базы - это важно!
            $file->delete();
            
        } catch (\Exception $e) {
            \Log::error('Error deleting topic file', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
        }
    }
/**
     * Увеличить количество просмотров
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }
    /**
     * Удаление файла поста
     */
    private static function deletePostFile($file)
    {
        try {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
                \Log::info('Deleted post file', [
                    'file_id' => $file->id,
                    'path' => $file->file_path,
                    'name' => $file->original_name
                ]);
            }
            
            // Удаляем запись из базы - это важно!
            $file->delete();
            
        } catch (\Exception $e) {
            \Log::error('Error deleting post file', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * Обновить счетчики темы
     */
    public function updateCounters(): void
    {
        $this->update([
            'replies_count' => $this->posts()->count(),
            'likes_count' => $this->likes()->count(),
            'last_activity_at' => $this->posts()->latest()->first()?->created_at ?? $this->created_at
        ]);
    }
    public function markAsEdited()
{
    $this->update([
        'edited_at' => now(),
        'edit_count' => ($this->edit_count ?? 0) + 1
    ]);
}
public function shouldShowPinIconOnCategoryPage(): bool
{
    return in_array($this->pin_type, ['global', 'category']);
}
/**
 * Галерея изображений темы
 */
public function galleryImages()
{
    return $this->hasMany(TopicGalleryImage::class)->ordered();
}

/**
 * Проверяет, разрешена ли галерея для категории темы
 */
public function allowsGallery()
{
    return $this->category && $this->category->allowsGallery();
}
/**
 * Удаление изображений из HTML контента
 */
private static function cleanupContentImages($content)
{
    if (empty($content)) {
        return;
    }

    // Ищем все изображения в контенте
    preg_match_all('/<img[^>]*src=["\']([^"\']*(?:\/storage\/images\/|images\/)[^"\']*)["\'][^>]*>/i', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $imageUrl) {
            // Нормализуем путь - убираем домен и /storage/
            $imagePath = $imageUrl;
            
            if (strpos($imageUrl, 'http') === 0) {
                $parsedUrl = parse_url($imageUrl);
                $imagePath = $parsedUrl['path'] ?? '';
            }
            
            $imagePath = preg_replace('#^/storage/#', '', $imagePath);
            
            try {
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                    \Log::info('Deleted content image', ['path' => $imagePath]);
                }
            } catch (\Exception $e) {
                \Log::error('Error deleting content image', [
                    'path' => $imagePath,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

}