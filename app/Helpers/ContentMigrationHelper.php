<?php

namespace App\Helpers;

class ContentMigrationHelper
{
    /**
     * Конвертирует старый markdown контент в HTML для Quill
     */
    public static function convertMarkdownToHtml($content)
    {
        // Если контент уже HTML (содержит теги) - возвращаем как есть
        if (strip_tags($content) !== $content) {
            return $content;
        }

        // Простая конвертация основных markdown элементов в HTML
        $html = $content;
        
        // **bold** -> <strong>bold</strong>
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // *italic* -> <em>italic</em>
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // `code` -> <code>code</code>
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // > quote -> <blockquote>quote</blockquote>
        $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);
        
        // [link](url) -> <a href="url">link</a>
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $html);
        
        // ![alt](url) -> <img src="url" alt="alt">
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" class="clickable-image">', $html);
        
        // Переносы строк
        $html = nl2br($html);
        
        return $html;
    }
}