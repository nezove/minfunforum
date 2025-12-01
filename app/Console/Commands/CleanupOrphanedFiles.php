<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\TopicFile;
use App\Models\PostFile;
use App\Models\Topic;
use App\Models\Post;

class CleanupOrphanedFiles extends Command
{
    protected $signature = 'forum:cleanup-files {--dry-run : Показать файлы без удаления}';
    protected $description = 'Очистка файлов-сирот и неиспользуемых изображений';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('РЕЖИМ ПРЕДВАРИТЕЛЬНОГО ПРОСМОТРА - файлы НЕ будут удалены');
        } else {
            $this->info('ВНИМАНИЕ: Файлы будут УДАЛЕНЫ навсегда!');
            if (!$this->confirm('Продолжить?')) {
                $this->info('Операция отменена');
                return;
            }
        }

        $this->cleanupAttachedFiles($isDryRun);
        $this->cleanupContentImages($isDryRun);
        
        $this->info('Очистка завершена!');
    }

    /**
     * Очистка прикрепленных файлов-сирот
     */
    private function cleanupAttachedFiles($isDryRun)
    {
        $this->info('Поиск прикрепленных файлов-сирот...');
        
        // Получаем все файлы из storage
        $allFiles = collect();
        
        // Добавляем файлы из разных папок
        $directories = ['files', 'files', 'uploads'];
        foreach ($directories as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $files = Storage::disk('public')->allFiles($dir);
                foreach ($files as $file) {
                    $allFiles->push($file);
                }
            }
        }
        
        // Собираем пути файлов из БД
        $dbFiles = collect();
        
        // Файлы тем
        TopicFile::all()->each(function($file) use ($dbFiles) {
            $dbFiles->push($file->file_path);
        });
        
        // Файлы постов
        PostFile::all()->each(function($file) use ($dbFiles) {
            $dbFiles->push($file->file_path);
        });
        
        // Находим файлы-сироты
        $orphanedFiles = $allFiles->diff($dbFiles);
        
        $this->info("Найдено {$orphanedFiles->count()} файлов-сирот в прикрепленных файлах");
        
        if ($orphanedFiles->count() > 0) {
            foreach ($orphanedFiles as $file) {
                $fileSize = Storage::disk('public')->size($file);
                $this->line("- {$file} (" . $this->formatBytes($fileSize) . ")");
                
                if (!$isDryRun) {
                    Storage::disk('public')->delete($file);
                }
            }
        }
    }

    /**
     * Очистка изображений из контента
     */
    private function cleanupContentImages($isDryRun)
    {
        $this->info('Поиск неиспользуемых изображений...');
        
        // Получаем все изображения из storage
        $allImages = collect();
        $imageDirs = ['images/originals', 'images/thumbnails'];
        
        foreach ($imageDirs as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $images = Storage::disk('public')->allFiles($dir);
                foreach ($images as $image) {
                    $allImages->push($image);
                }
            }
        }
        
        // Собираем используемые изображения из контента
        $usedImages = collect();
        
        // Из тем
        Topic::all()->each(function($topic) use ($usedImages) {
            $this->extractImagesFromContent($topic->content, $usedImages);
        });
        
        // Из постов
        Post::all()->each(function($post) use ($usedImages) {
            $this->extractImagesFromContent($post->content, $usedImages);
        });
        
        // Находим неиспользуемые изображения
        $unusedImages = $allImages->diff($usedImages);
        
        $this->info("Найдено {$unusedImages->count()} неиспользуемых изображений");
        
        if ($unusedImages->count() > 0) {
            foreach ($unusedImages as $image) {
                $fileSize = Storage::disk('public')->size($image);
                $this->line("- {$image} (" . $this->formatBytes($fileSize) . ")");
                
                if (!$isDryRun) {
                    Storage::disk('public')->delete($image);
                }
            }
        }
    }

    /**
     * Извлечение путей изображений из HTML контента
     */
    private function extractImagesFromContent($content, $usedImages)
    {
        if (empty($content)) {
            return;
        }

        // Ищем все изображения в контенте
        preg_match_all('/<img[^>]*src=["\']([^"\']*(?:\/storage\/images\/|images\/)[^"\']*)["\'][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $imageUrl) {
                // Нормализуем путь
                $imagePath = $imageUrl;
                
                // Если это полный URL, извлекаем путь
                if (strpos($imageUrl, 'http') === 0) {
                    $parsedUrl = parse_url($imageUrl);
                    $imagePath = $parsedUrl['path'] ?? '';
                }
                
                // Убираем /storage/ из начала пути
                $imagePath = preg_replace('#^/storage/#', '', $imagePath);
                
                if (!empty($imagePath)) {
                    $usedImages->push($imagePath);
                    
                    // Также добавляем связанные файлы (превью/оригинал)
                    if (strpos($imagePath, '_original.jpg') !== false) {
                        $thumbnailPath = str_replace('_original.jpg', '_thumb.jpg', $imagePath);
                        $thumbnailPath = str_replace('/originals/', '/thumbnails/', $thumbnailPath);
                        $usedImages->push($thumbnailPath);
                    }
                    
                    if (strpos($imagePath, '_thumb.jpg') !== false) {
                        $originalPath = str_replace('_thumb.jpg', '_original.jpg', $imagePath);
                        $originalPath = str_replace('/thumbnails/', '/originals/', $originalPath);
                        $usedImages->push($originalPath);
                    }
                }
            }
        }
    }

    /**
     * Форматирование размера файла
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    }