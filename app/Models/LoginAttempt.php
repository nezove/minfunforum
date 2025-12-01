<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'email',
        'successful',
        'attempted_at'
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime'
    ];

    /**
     * Записать попытку входа
     */
    public static function recordAttempt($ip, $email = null, $successful = false)
    {
        return self::create([
            'ip_address' => $ip,
            'email' => $email,
            'successful' => $successful,
            'attempted_at' => now()
        ]);
    }

    /**
     * Получить количество неудачных попыток для IP за последние 24 часа
     */
    public static function getFailedAttemptsForIp($ip, $hours = 24)
    {
        return self::where('ip_address', $ip)
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Получить количество неудачных попыток для email за последние 24 часа
     */
    public static function getFailedAttemptsForEmail($email, $hours = 24)
    {
        if (!$email) {
            return 0;
        }

        return self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Проверить, заблокирован ли IP
     */
    public static function isIpBlocked($ip)
    {
        $failedAttempts = self::getFailedAttemptsForIp($ip, 48); // За последние 48 часов
        return $failedAttempts >= 8;
    }

    /**
     * Проверить, нужна ли капча для IP
     */
    public static function requiresCaptcha($ip)
    {
        $failedAttempts = self::getFailedAttemptsForIp($ip, 24); // За последние 24 часа
        return $failedAttempts >= 1; // После первой неудачной попытки
    }

    /**
     * Очистить старые записи (старше 48 часов)
     */
    public static function cleanup()
    {
        self::where('attempted_at', '<', Carbon::now()->subHours(48))->delete();
    }

    /**
     * Получить время до разблокировки IP
     */
    public static function getTimeUntilUnblock($ip)
{
    $lastAttempt = self::where('ip_address', $ip)
        ->where('successful', false)
        ->latest('attempted_at')
        ->first();

    if (!$lastAttempt) {
        return null;
    }

    // ИСПРАВЛЕНИЕ: Используем copy() чтобы не изменять исходный объект!
    $unblockTime = $lastAttempt->attempted_at->copy()->addHours(48);
    return $unblockTime->isFuture() ? $unblockTime : null;
}

}