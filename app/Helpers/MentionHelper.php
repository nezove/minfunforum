<?php

// app/Helpers/MentionHelper.php

namespace App\Helpers;

use App\Models\User;

class MentionHelper
{
    /**
     * Преобразует @username в ссылки на профили пользователей
     * ВАЖНО: В ссылках всегда показывается username (логин), а не отображаемое имя
     * Это обеспечивает консистентность - если пользователь написал @admin, 
     * то и в ссылке будет показано @admin, а не @"Администратор"
     */
    public static function parseUserMentions($text)
    {
        // Убеждаемся что текст в UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        return preg_replace_callback(
            '/@([a-zA-Zа-яёА-ЯЁ0-9_]+)/u', // добавляем флаг 'u' для UTF-8
            function ($matches) {
                $username = $matches[1];
                
                // ИСПРАВЛЕНО: Ищем пользователя по username, а не по name
                $user = User::whereRaw('LOWER(username) = LOWER(?)', [$username])->first();
                
                if ($user) {
                    $profileUrl = route('profile.show', $user->id);
                    // В упоминаниях ВСЕГДА показываем username (логин), не имя
                    return '<a href="' . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . '" class="text-primary fw-semibold text-decoration-none user-mention" title="Перейти к профилю">@' . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') . '</a>';
                }
                
                // Если пользователь не найден, возвращаем как есть
                return '@' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            },
            $text
        );
    }

    /**
     * Находит всех упомянутых пользователей в тексте
     */
    public static function findMentionedUsers($text)
    {
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }

        preg_match_all('/@([a-zA-Zа-яёА-ЯЁ0-9_]+)/u', $text, $matches);
        
        if (empty($matches[1])) {
            return collect();
        }

        $usernames = array_unique($matches[1]);
        
        // ИСПРАВЛЕНО: Ищем по username, а не по name
        return User::whereIn('username', $usernames)->get();
    }
}