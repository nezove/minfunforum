<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Генерация title для главной страницы
     */
    public static function generateHomeTitle($siteName)
    {
        return "{$siteName} - Форум разработчиков и IT сообщество";
    }

    /**
     * Генерация description для главной страницы
     */
    public static function generateHomeDescription($topicsCount, $usersCount)
    {
        return "Присоединяйтесь к нашему IT сообществу! {$topicsCount} тем для обсуждения, {$usersCount} активных разработчиков. Обсуждайте программирование, веб-разработку, новые технологии.";
    }

    /**
     * Генерация title для категории
     */
    public static function generateCategoryTitle($categoryName, $topicsCount, $siteName)
    {
        return "{$categoryName} ({$topicsCount} тем) - Форум {$siteName}";
    }

    /**
     * Генерация description для категории
     */
    public static function generateCategoryDescription($categoryName, $categoryDescription = null, $topicsCount = 0)
    {
        $description = $categoryDescription ?: "Обсуждения в категории \"{$categoryName}\"";
        
        if ($topicsCount > 0) {
            $description .= ". {$topicsCount} " . self::pluralize($topicsCount, 'активная тема', 'активные темы', 'активных тем') . ' для обсуждения';
        }
        
        return $description;
    }

    /**
     * Генерация title для темы
     */
    public static function generateTopicTitle($topicTitle, $categoryName, $siteName)
    {
        return "{$topicTitle} - {$categoryName} | Форум {$siteName}";
    }

    /**
     * Генерация description для темы
     */
    public static function generateTopicDescription($topicContent, $authorName, $repliesCount = 0, $categoryName = '')
    {
        // Обрезаем контент до 150 символов
        $cleanContent = strip_tags($topicContent);
        $shortContent = mb_substr($cleanContent, 0, 150);
        if (mb_strlen($cleanContent) > 150) {
            $shortContent .= '...';
        }
        
        $description = $shortContent;
        
        if ($repliesCount > 0) {
            $description .= " | {$repliesCount} " . self::pluralize($repliesCount, 'ответ', 'ответа', 'ответов');
        }
        
        if (!empty($categoryName)) {
            $description .= " | Категория: {$categoryName}";
        }
        
        $description .= " | Автор: {$authorName}";
        
        return $description;
    }

    /**
     * Генерация title для страницы тега
     */
    public static function generateTagTitle($tagName, $categoryName, $topicsCount, $siteName)
    {
        return "Тег \"{$tagName}\" в категории \"{$categoryName}\" ({$topicsCount} тем) - {$siteName}";
    }

    /**
     * Генерация description для страницы тега
     */
    public static function generateTagDescription($tagName, $categoryName, $tagDescription = null, $topicsCount = 0)
    {
        $description = $tagDescription ?: "Обсуждения по тегу \"{$tagName}\" в категории \"{$categoryName}\"";
        
        if ($topicsCount > 0) {
            $description .= ". Найдено {$topicsCount} " . self::pluralize($topicsCount, 'тема', 'темы', 'тем');
        }
        
        return $description;
    }

    /**
     * Генерация ключевых слов
     */
    public static function generateKeywords(array $keywords)
    {
        // Фильтруем пустые значения и убираем дубликаты
        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);
        
        return implode(', ', $keywords);
    }

    /**
     * Очистка текста для SEO
     */
    public static function cleanTextForSeo($text, $maxLength = 160)
    {
        // Убираем HTML теги
        $text = strip_tags($text);
        
        // Убираем лишние пробелы и переносы строк
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Обрезаем до нужной длины
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            
            // Обрезаем по последнему пробелу, чтобы не разрывать слова
            $lastSpace = mb_strrpos($text, ' ');
            if ($lastSpace !== false) {
                $text = mb_substr($text, 0, $lastSpace);
            }
            
            $text .= '...';
        }
        
        return $text;
    }

    /**
     * Простая очистка текста (алиас для cleanTextForSeo)
     */
    public static function cleanText($text, $maxLength = 160)
    {
        return self::cleanTextForSeo($text, $maxLength);
    }

    /**
     * Генерация slug из строки
     */
    public static function generateSlug($string)
    {
        // Транслитерация русских символов
        $transliterationMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];
        
        // Применяем транслитерацию
        $string = strtr($string, $transliterationMap);
        
        // Преобразуем в slug
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        $string = trim($string, '-');
        
        return $string;
    }

    /**
     * Генерация Open Graph тегов
     */
    public static function generateOpenGraphTags($title, $description, $url, $imageUrl = null)
    {
        $tags = [
            'og:title' => $title,
            'og:description' => self::cleanTextForSeo($description, 300),
            'og:url' => $url,
            'og:type' => 'website',
            'og:site_name' => config('app.name', 'Forum'),
        ];
        
        if ($imageUrl) {
            $tags['og:image'] = $imageUrl;
        }
        
        return $tags;
    }

    /**
     * Генерация Twitter Card тегов
     */
    public static function generateTwitterCardTags($title, $description, $imageUrl = null)
    {
        $tags = [
            'twitter:card' => 'summary',
            'twitter:title' => $title,
            'twitter:description' => self::cleanTextForSeo($description, 200),
        ];
        
        if ($imageUrl) {
            $tags['twitter:image'] = $imageUrl;
            $tags['twitter:card'] = 'summary_large_image';
        }
        
        return $tags;
    }

    /**
     * Вспомогательная функция для склонения слов
     */
    private static function pluralize($count, $one, $two, $five)
    {
        $count = abs($count) % 100;
        $lastDigit = $count % 10;
        
        if ($count > 10 && $count < 20) return $five;
        if ($lastDigit > 1 && $lastDigit < 5) return $two;
        if ($lastDigit == 1) return $one;
        
        return $five;
    }

    /**
     * Валидация SEO данных
     */
    public static function validateSeoData($title, $description, $keywords = null)
    {
        $errors = [];
        
        // Проверка title
        if (empty($title)) {
            $errors['title'] = 'Title не может быть пустым';
        } elseif (mb_strlen($title) > 60) {
            $errors['title'] = 'Title слишком длинный (рекомендуется до 60 символов)';
        } elseif (mb_strlen($title) < 30) {
            $errors['title'] = 'Title слишком короткий (рекомендуется от 30 символов)';
        }
        
        // Проверка description
        if (empty($description)) {
            $errors['description'] = 'Description не может быть пустым';
        } elseif (mb_strlen($description) > 160) {
            $errors['description'] = 'Description слишком длинный (рекомендуется до 160 символов)';
        } elseif (mb_strlen($description) < 120) {
            $errors['description'] = 'Description слишком короткий (рекомендуется от 120 символов)';
        }
        
        // Проверка keywords
        if ($keywords && mb_strlen($keywords) > 255) {
            $errors['keywords'] = 'Keywords слишком длинные (максимум 255 символов)';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Генерация структурированных данных (JSON-LD)
     */
    public static function generateStructuredData($type, $data)
    {
        $baseStructure = [
            '@context' => 'https://schema.org',
            '@type' => $type
        ];
        
        switch ($type) {
            case 'WebSite':
                return array_merge($baseStructure, [
                    'name' => $data['name'] ?? config('app.name'),
                    'url' => $data['url'] ?? config('app.url'),
                    'description' => $data['description'] ?? '',
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ($data['url'] ?? config('app.url')) . '/search?q={search_term_string}',
                        'query-input' => 'required name=search_term_string'
                    ]
                ]);
                
            case 'DiscussionForumPosting':
                return array_merge($baseStructure, [
                    'headline' => $data['title'] ?? '',
                    'text' => $data['content'] ?? '',
                    'author' => [
                        '@type' => 'Person',
                        'name' => $data['author'] ?? ''
                    ],
                    'datePublished' => $data['published_at'] ?? '',
                    'dateModified' => $data['modified_at'] ?? '',
                    'url' => $data['url'] ?? ''
                ]);
                
            case 'BreadcrumbList':
                $items = [];
                foreach ($data['items'] as $index => $item) {
                    $items[] = [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['name'],
                        'item' => $item['url'] ?? null
                    ];
                }
                
                return array_merge($baseStructure, [
                    'itemListElement' => $items
                ]);
                
            default:
                return $baseStructure;
        }
    }
    /**
 * Генерация title для профиля пользователя
 */
public static function generateProfileTitle($userLogin, $siteName)
{
    return "{$userLogin} - Профиль пользователя | {$siteName}";
}

/**
 * Генерация description для профиля пользователя
 */
public static function generateProfileDescription(
    $userLogin, 
    $topicsCount, 
    $postsCount, 
    $role = 'user', 
    $bio = null,
    $location = null
) {
    $description = "Профиль пользователя {$userLogin}";
    
    if (!empty($bio)) {
        $cleanBio = self::cleanTextForSeo($bio, 100);
        $description .= ". {$cleanBio}";
    }
    
    $description .= ". {$topicsCount} " . self::pluralize($topicsCount, 'созданная тема', 'созданные темы', 'созданных тем');
    $description .= ", {$postsCount} " . self::pluralize($postsCount, 'сообщение', 'сообщения', 'сообщений');
    
    if (!empty($role) && $role !== 'user') {
        $description .= ". Роль: {$role}";
    }
    
    if (!empty($location)) {
        $description .= ". Местоположение: {$location}";
    }
    
    return $description;
}
}