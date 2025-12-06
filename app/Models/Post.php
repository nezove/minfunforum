<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'user_id',
        'topic_id',
        'parent_id',
        'quoted_content',
        'reply_to_post_id',
        'edited_at',
        'edit_count'
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    // === СВЯЗИ ===

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    public function replyToPost()
    {
        return $this->belongsTo(Post::class, 'reply_to_post_id');
    }

    public function files()
    {
        return $this->hasMany(PostFile::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // === МЕТОДЫ ===

    /**
     * Проверка, понравился ли пост пользователю
     */
    public function isLikedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        $userId = is_object($user) ? $user->id : $user;
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Проверяет, можно ли редактировать пост
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
        
        // Проверяем, что это автор поста
        if ($this->user_id !== $user->id) {
            return false;
        }
        
        // Проверяем временное ограничение
        $timeLimit = env('POST_EDIT_TIME_LIMIT', 1440); // минуты (24 часа по умолчанию)
        $timeLimitExpired = $this->created_at->addMinutes($timeLimit)->isPast();
        
        return !$timeLimitExpired;
    }

    /**
     * Проверяет, можно ли удалять пост
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
        
        // Проверяем, что это автор поста
        if ($this->user_id !== $user->id) {
            return false;
        }
        
        // Проверяем временное ограничение
        $timeLimit = env('POST_EDIT_TIME_LIMIT', 1440); // минуты
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

    /**
     * Получить ссылку на пост
     */
    public function getPermalinkAttribute()
    {
        return route('topics.show', $this->topic) . '#post-' . $this->id;
    }

    public function getLikesCountAttribute()
    {
        return $this->likes()->count();
    }

    // === СОБЫТИЯ МОДЕЛИ ===

    protected static function booted()
    {
        // При удалении поста удаляем связанные файлы и изображения
        static::deleting(function ($post) {
            \Log::info('Deleting post with files cleanup', [
                'post_id' => $post->id,
                'topic_id' => $post->topic_id
            ]);

            try {
                // 1. Удаляем изображения из контента поста
                self::deleteContentImages($post->content);

                // 2. Удаляем прикрепленные файлы поста
                foreach ($post->files as $file) {
                    self::deletePostFile($file);
                }

                // 3. Удаляем лайки поста
                $post->likes()->delete();

                // 4. Удаляем уведомления, связанные с постом
                \App\Models\Notification::where('data->post_id', $post->id)->delete();

                \Log::info('Post files cleanup completed', ['post_id' => $post->id]);

            } catch (\Exception $e) {
                \Log::error('Error during post deletion cleanup', [
                    'post_id' => $post->id,
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
                        \Log::info('Deleted post content image', ['path' => $imagePath]);
                    }
                    
                    // Удаляем превью (если это оригинал)
                    if (strpos($imagePath, '_original.jpg') !== false) {
                        $thumbnailPath = str_replace('_original.jpg', '_thumb.jpg', $imagePath);
                        $thumbnailPath = str_replace('/originals/', '/thumbnails/', $thumbnailPath);
                        
                        if (Storage::disk('public')->exists($thumbnailPath)) {
                            Storage::disk('public')->delete($thumbnailPath);
                            \Log::info('Deleted post thumbnail', ['path' => $thumbnailPath]);
                        }
                    }
                    
                    // Удаляем оригинал (если это превью)
                    if (strpos($imagePath, '_thumb.jpg') !== false) {
                        $originalPath = str_replace('_thumb.jpg', '_original.jpg', $imagePath);
                        $originalPath = str_replace('/thumbnails/', '/originals/', $originalPath);
                        
                        if (Storage::disk('public')->exists($originalPath)) {
                            Storage::disk('public')->delete($originalPath);
                            \Log::info('Deleted post original image', ['path' => $originalPath]);
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Error deleting post content image', [
                        'path' => $imagePath,
                        'url' => $imageUrl,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
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
    public function markAsEdited()
{
    $this->update([
        'edited_at' => now(),
        'edit_count' => $this->edit_count + 1
    ]);
}
}