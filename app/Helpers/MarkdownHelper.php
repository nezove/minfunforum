<?php

namespace App\Helpers;

class MarkdownHelper
{
    /**
     * Конвертирует HTML изображения обратно в markdown для редактирования
     */
    public static function convertHtmlToMarkdownForEdit($content)
    {
        if (empty($content)) {
            return $content;
        }

        // Паттерн для поиска img тегов созданных нашим редактором
        $pattern = '/<img\s+src="([^"]*\/storage\/images\/thumbnails\/[^"]*_thumb\.jpg)"\s+data-original="([^"]*\/storage\/images\/originals\/[^"]*_original\.jpg)"\s+class="clickable-image"\s+alt="([^"]*)"\s+title="[^"]*">/i';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $thumbnailUrl = $matches[1];
            $originalUrl = $matches[2]; 
            $altText = $matches[3];
            
            // Возвращаем markdown формат с оригинальной ссылкой
            return "![{$altText}]({$originalUrl})";
        }, $content);

        // Также обрабатываем простые img теги без data-original
        $simplePattern = '/<img\s+src="([^"]*\/storage\/images\/originals\/[^"]*_original\.jpg)"\s+[^>]*alt="([^"]*)"/i';
        
        $content = preg_replace_callback($simplePattern, function($matches) {
            $imageUrl = $matches[1];
            $altText = $matches[2];
            
            return "![{$altText}]({$imageUrl})";
        }, $content);

        return $content;
    }

    /**
     * Конвертирует markdown изображения в HTML для отображения
     */
    public static function convertMarkdownToHtmlForDisplay($content)
    {
        if (empty($content)) {
            return $content;
        }

        // Паттерн для поиска markdown изображений
        $pattern = '/!\[([^\]]*)\]\(([^)]*\/storage\/images\/originals\/[^)]*_original\.jpg)\)/i';
        
        $content = preg_replace_callback($pattern, function($matches) {
            $altText = $matches[1] ?: 'Изображение';
            $originalUrl = $matches[2];
            
            // Получаем URL превью, заменяя _original на _thumb и папку
            $thumbnailUrl = str_replace(
                ['/originals/', '_original.jpg'], 
                ['/thumbnails/', '_thumb.jpg'], 
                $originalUrl
            );
            
            // Возвращаем HTML тег с превью и ссылкой на оригинал
            return '<img src="' . $thumbnailUrl . '" data-original="' . $originalUrl . '" class="clickable-image" alt="' . htmlspecialchars($altText) . '" title="Нажмите для увеличения">';
        }, $content);

        return $content;
    }
}