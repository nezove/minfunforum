<?php

namespace App\Services;

use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SessionLogger
{
    /**
     * Безопасное получение IP адреса
     */
    public static function getRealIp(Request $request): string
    {
        // Проверяем заголовки в порядке приоритета
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',      // Cloudflare
            'HTTP_X_REAL_IP',             // Nginx
            'HTTP_X_FORWARDED_FOR',       // Общий прокси
            'HTTP_X_CLIENT_IP',           // Прокси
            'REMOTE_ADDR'                 // Прямое соединение
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Проверяем что IP валидный и публичный
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback к прямому IP
        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Безопасное получение User-Agent
     */
    public static function getSafeUserAgent(Request $request): string
    {
        $userAgent = $request->userAgent() ?? 'Unknown';
        
        // Ограничиваем длину и очищаем от потенциально опасных символов
        $userAgent = substr($userAgent, 0, 500);
        $userAgent = preg_replace('/[^\x20-\x7E]/', '', $userAgent); // Только печатные ASCII
        
        return $userAgent;
    }

    /**
     * Шифрование IP для безопасного хранения (обратимое)
     */
    public static function encryptIp(string $ip): string
    {
        try {
            return encrypt($ip);
        } catch (\Exception $e) {
            // Fallback - если шифрование не работает, используем base64
            return base64_encode($ip);
        }
    }

    /**
     * Расшифровка IP
     */
    public static function decryptIp(string $encryptedIp): string
    {
        try {
            return decrypt($encryptedIp);
        } catch (\Exception $e) {
            // Fallback для старых записей или base64
            $decoded = base64_decode($encryptedIp, true);
            return $decoded !== false ? $decoded : 'Unknown';
        }
    }

    /**
     * Логирование сессии пользователя
     */
    public static function logSession(int $userId, string $sessionType, Request $request): void
    {
        try {
            $ip = self::getRealIp($request);
            $userAgent = self::getSafeUserAgent($request);
            
            // Шифруем IP для безопасного хранения (но с возможностью расшифровки)
            $encryptedIp = self::encryptIp($ip);

            $session = UserSession::create([
                'user_id' => $userId,
                'ip_address' => $encryptedIp,
                'user_agent' => $userAgent,
                'session_type' => $sessionType,
                'created_at' => now(),
            ]);

            // Очищаем старые записи (оставляем только последние 50 для каждого пользователя)
            self::cleanupOldSessions($userId);

        } catch (\Exception $e) {
            Log::error('SessionLogger: Failed to log session', [
                'user_id' => $userId,
                'session_type' => $sessionType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Очистка старых сессий
     */
    private static function cleanupOldSessions(int $userId): void
    {
        try {
            // Считаем общее количество сессий для пользователя
            $totalSessions = UserSession::where('user_id', $userId)->count();
            
            // Если сессий больше 50, удаляем старые
            if ($totalSessions > 50) {
                $sessionsToDelete = UserSession::where('user_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->skip(50)
                    ->take($totalSessions - 50)
                    ->pluck('id');

                if ($sessionsToDelete->isNotEmpty()) {
                    UserSession::whereIn('id', $sessionsToDelete)->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error('SessionLogger: Failed to cleanup old sessions', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получение последних сессий пользователя с расшифрованными IP
     */
    public static function getUserSessions(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $sessions = UserSession::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        // Расшифровываем IP для отображения
        foreach ($sessions as $session) {
            $session->ip_readable = self::decryptIp($session->ip_address);
        }

        return $sessions;
    }

    /**
     * Проверка подозрительной активности с расшифровкой IP
     */
    public static function checkSuspiciousActivity(int $userId): array
    {
        $recentSessions = UserSession::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        // Расшифровываем IP для анализа
        $uniqueIps = collect();
        foreach ($recentSessions as $session) {
            $ip = self::decryptIp($session->ip_address);
            $uniqueIps->push($ip);
        }

        $uniqueUserAgents = $recentSessions->pluck('user_agent')->unique()->count();

        return [
            'multiple_ips' => $uniqueIps->unique()->count() > 3,
            'multiple_user_agents' => $uniqueUserAgents > 2,
            'rapid_logins' => $recentSessions->where('session_type', 'login')->count() > 10,
        ];
    }
}