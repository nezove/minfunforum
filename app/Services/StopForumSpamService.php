<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StopForumSpamService
{
    private $apiKey;
    private $apiUrl;
    private $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.stopforumspam.api_key');
        $this->apiUrl = config('services.stopforumspam.api_url');
        $this->enabled = config('services.stopforumspam.enabled', false);
    }

    /**
     * Проверка IP адреса на спам
     */
    public function checkIp(string $ip): bool
    {
            // Временная заглушка для тестирования
    $testIps = [
        '188.233.102.15' => false,  // для теста, если false - отключить. Если true - включить блокировку
    ];
    
    if (array_key_exists($ip, $testIps)) {
        return $testIps[$ip];
    }

        if (!$this->enabled || !$this->apiKey) {
            return false;
        }

        // Кэшируем результат на 1 час
        $cacheKey = "stopforumspam_ip_{$ip}";
        
        return Cache::remember($cacheKey, 3600, function () use ($ip) {
            try {
                $response = Http::timeout(5)->get($this->apiUrl, [
                    'ip' => $ip,
                    'f' => 'json',
                    'confidence' => 1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Логируем успешный ответ для отладки
                    Log::info('StopForumSpam IP check', [
                        'ip' => $ip,
                        'response' => $data
                    ]);

                    return isset($data['ip']['appears']) && $data['ip']['appears'] == 1;
                }
            } catch (\Exception $e) {
                Log::error('StopForumSpam IP check failed', [
                    'ip' => $ip,
                    'error' => $e->getMessage()
                ]);
            }

            return false;
        });
    }

    /**
     * Проверка email на спам
     */
    public function checkEmail(string $email): bool
    {
        if (!$this->enabled || !$this->apiKey) {
            return false;
        }

        // Кэшируем результат на 24 часа
        $cacheKey = "stopforumspam_email_" . md5($email);
        
        return Cache::remember($cacheKey, 86400, function () use ($email) {
            try {
                $response = Http::timeout(5)->get($this->apiUrl, [
                    'email' => $email,
                    'f' => 'json',
                    'confidence' => 1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Логируем успешный ответ для отладки
                    Log::info('StopForumSpam Email check', [
                        'email' => $email,
                        'response' => $data
                    ]);

                    return isset($data['email']['appears']) && $data['email']['appears'] == 1;
                }
            } catch (\Exception $e) {
                Log::error('StopForumSpam Email check failed', [
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }

            return false;
        });
    }

    /**
     * Комплексная проверка IP и email
     */
    public function checkBoth(string $ip, string $email): array
    {
        return [
            'ip_spam' => $this->checkIp($ip),
            'email_spam' => $this->checkEmail($email),
        ];
    }

    /**
     * Получить IP адрес пользователя с учетом прокси
     */
    public static function getUserIp(): string
    {
        // Проверяем различные заголовки для получения реального IP
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                // Проверяем, что IP валидный
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}