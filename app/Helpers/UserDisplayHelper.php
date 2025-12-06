<?php

// app/Helpers/UserDisplayHelper.php

namespace App\Helpers;

class UserDisplayHelper
{
    /**
     * Получить отображаемое имя пользователя в зависимости от настроек
     */
    public static function getDisplayName($user)
    {
        if (!$user) {
            return '';
        }
        
        // Проверяем настройку из .env файла
        $displayUsername = config('app.display_username_instead_of_name', false);
        
        return $displayUsername ? $user->username : $user->name;
    }
    
    /**
     * Получить имя для упоминаний (всегда username для совместимости)
     */
    public static function getMentionName($user)
    {
        if (!$user) {
            return '';
        }
        
        // В упоминаниях ВСЕГДА используем username
        return $user->username;
    }
    
    /**
     * Проверить, должны ли упоминания сохранять исходный логин
     */
    public static function shouldPreserveMentionUsername()
    {
        // Всегда сохраняем username в упоминаниях для консистентности
        return true;
    }

    /**
     * Получить отображаемое имя с индикатором блокировки
     */
    public static function getDisplayNameWithBanStatus($user)
    {
        if (!$user) {
            return '';
        }

        $name = self::getDisplayName($user);
        
        if ($user->isBanned()) {
            $banType = $user->getBanType();
            if ($banType === 'permanent') {
                return $name . ' <span class="text-muted">(заблокирован навсегда)</span>';
            } else {
                return $name . ' <span class="text-muted">(заблокирован до ' . $user->banned_until->format('d.m.Y') . ')</span>';
            }
        }

        return $name;
    }

    /**
     * Получить бейдж статуса пользователя
     */
    public static function getUserStatusBadge($user)
    {
        if (!$user) {
            return '';
        }

        if ($user->isBanned()) {
            $banType = $user->getBanType();
            if ($banType === 'permanent') {
                return '<span class="badge bg-danger ms-1"><i class="bi bi-person-x me-1"></i>Заблокирован</span>';
            } else {
                return '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-person-dash me-1"></i>Ограничен</span>';
            }
        }

        // Показываем роль, если не обычный пользователь
        if ($user->role !== 'user') {
            $colorClass = $user->role === 'admin' ? 'bg-danger' : 'bg-warning';
            $roleName = $user->role === 'admin' ? 'Администратор' : 'Модератор';
            return '<span class="badge ' . $colorClass . ' ms-1">' . $roleName . '</span>';
        }

        return '';
    }

    /**
     * Проверить, может ли пользователь взаимодействовать (ставить лайки, отвечать)
     */
    public static function canUserInteract($user)
    {
        if (!$user) {
            return false;
        }

        return $user->canPerformActions();
    }

    /**
     * Получить CSS класс для заблокированного пользователя
     */
    public static function getBannedUserClass($user)
    {
        if (!$user || !$user->isBanned()) {
            return '';
        }

        return 'user-banned';
    }

    /**
     * Получить текст о статусе блокировки для профиля
     */
    public static function getBanStatusText($user)
    {
        if (!$user || !$user->isBanned()) {
            return null;
        }

        $banType = $user->getBanType();
        
        if ($banType === 'permanent') {
            $text = 'Этот пользователь заблокирован навсегда.';
        } else {
            $text = 'Этот пользователь временно заблокирован до ' . $user->banned_until->format('d.m.Y в H:i') . '.';
        }

        if ($user->ban_reason) {
            $text .= ' Причина: ' . $user->ban_reason;
        }

        return $text;
    }
}