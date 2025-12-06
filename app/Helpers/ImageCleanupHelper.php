<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImageCleanupHelper
{
    /**
     * Извлекает имена файлов изображений из markdown контента
     */
    public static function extractImageFilenames($content)
    {
        $filenames = [];
        
        // Ищем изображения в формате ![alt](url)
        preg_match_all('/!\[.*?\]\((.*?)\)/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                // Извлекаем имя файла из URL
                if (strpos($url, '/storage/images/') !== false) {
                    // Для изображений типа: /storage/images/originals/filename_original.jpg
                    if (preg_match('/\/storage\/images\/(?:originals|thumbnails)\/(.+?)(?:_original|_thumb)\.jpg/', $url, $fileMatch)) {
                        $filenames[] = $fileMatch[1];
                    }
                }
            }
        }
        
        return array_unique($filenames);
    }

    /**
     * Удаляет файлы изображений по именам
     */
    public static function deleteImageFiles($filenames)
    {
        $deletedCount = 0;
        
        foreach ($filenames as $filename) {
            try {
                // Удаляем оригинал
                $originalPath = "images/originals/{$filename}_original.jpg";
                if (Storage::disk('public')->exists($originalPath)) {
                    Storage::disk('public')->delete($originalPath);
                    $deletedCount++;
                    Log::info("Deleted original image: {$originalPath}");
                }
                
                // Удаляем превью
                $thumbnailPath = "images/thumbnails/{$filename}_thumb.jpg";
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    Storage::disk('public')->delete($thumbnailPath);
                    $deletedCount++;
                    Log::info("Deleted thumbnail image: {$thumbnailPath}");
                }
            } catch (\Exception $e) {
                Log::error("Error deleting image files for {$filename}: " . $e->getMessage());
            }
        }
        
        return $deletedCount;
    }

    /**
     * Очищает изображения из контента при удалении поста/темы
     */
    public static function cleanupContentImages($content)
    {
        $filenames = self::extractImageFilenames($content);
        return self::deleteImageFiles($filenames);
    }
}