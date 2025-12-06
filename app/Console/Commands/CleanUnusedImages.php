<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Topic;
use App\Models\Post;

class CleanUnusedImages extends Command
{
    protected $signature = 'images:clean-unused';
    protected $description = 'Clean unused images from storage';

    public function handle()
    {
        $thumbnailPath = 'images/thumbnails/';
        $originalPath = 'images/originals/';
        
        $this->info('Scanning for unused images...');
        
        // Получаем все файлы изображений
        $thumbnails = Storage::disk('public')->files($thumbnailPath);
        $originals = Storage::disk('public')->files($originalPath);
        
        $deletedCount = 0;
        
        foreach ($thumbnails as $thumbnail) {
            $thumbnailUrl = asset('storage/' . $thumbnail);
            
            // Проверяем, используется ли изображение в темах или постах
            $usedInTopics = Topic::where('content', 'LIKE', '%' . $thumbnailUrl . '%')->exists();
            $usedInPosts = Post::where('content', 'LIKE', '%' . $thumbnailUrl . '%')->exists();
            
            if (!$usedInTopics && !$usedInPosts) {
                // Удаляем превью и оригинал
                Storage::disk('public')->delete($thumbnail);
                
                $originalFile = str_replace('_thumb.jpg', '_original.jpg', $thumbnail);
                $originalFile = str_replace('/thumbnails/', '/originals/', $originalFile);
                
                if (Storage::disk('public')->exists($originalFile)) {
                    Storage::disk('public')->delete($originalFile);
                }
                
                $deletedCount++;
                $this->line('Deleted: ' . $thumbnail);
            }
        }
        
        $this->info("Cleanup completed. Deleted {$deletedCount} unused images.");
    }
}