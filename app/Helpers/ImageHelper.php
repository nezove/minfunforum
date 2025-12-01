<?php

// Создай файл app/Helpers/ImageHelper.php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Добавляет Bootstrap классы к изображениям в HTML
     */
    public static function addBootstrapClasses($html)
    {
        // Паттерн для поиска img тегов
        $pattern = '/<img([^>]*?)src="([^"]*?)"([^>]*?)>/i';
        
        $replacement = function($matches) {
            $beforeSrc = $matches[1];
            $src = $matches[2];
            $afterSrc = $matches[3];
            
            // Проверяем, есть ли уже class атрибут
            if (preg_match('/class="([^"]*?)"/i', $beforeSrc . $afterSrc, $classMatch)) {
                // Если есть, добавляем к существующим классам
                $existingClasses = $classMatch[1];
                $newClasses = $existingClasses . ' img-fluid rounded shadow-sm';
                $result = preg_replace('/class="([^"]*?)"/i', 'class="' . $newClasses . '"', $beforeSrc . $afterSrc);
                return '<img' . $beforeSrc . 'src="' . $src . '"' . $result . ' loading="lazy">';
            } else {
                // Если нет, добавляем class атрибут
                return '<img' . $beforeSrc . 'src="' . $src . '"' . $afterSrc . ' class="img-fluid rounded shadow-sm" loading="lazy">';
            }
        };
        
        return preg_replace_callback($pattern, $replacement, $html);
    }

    /**
     * Обрабатывает Markdown и добавляет Bootstrap классы к изображениям
     */
    public static function processMarkdownWithImages($markdown)
    {
        // Сначала конвертируем Markdown в HTML
        $html = \Illuminate\Support\Str::markdown($markdown);
        
        // Затем добавляем Bootstrap классы к изображениям
        return self::addBootstrapClasses($html);
    }
}