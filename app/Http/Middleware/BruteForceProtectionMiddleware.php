<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Session;

class BruteForceProtectionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем, есть ли класс LoginAttempt (может быть не загружен)
        if (!class_exists('App\Models\LoginAttempt')) {
            return $next($request);
        }

        $ip = $request->ip();

        try {
            // Очистка старых записей
            LoginAttempt::cleanup();

            // Проверяем, заблокирован ли IP
            if (LoginAttempt::isIpBlocked($ip)) {
                // ИСПРАВЛЕНИЕ: Не используем сломанную getTimeUntilUnblock()
                // Получаем время последней неудачной попытки напрямую
                $lastFailedAttempt = LoginAttempt::where('ip_address', $ip)
                    ->where('successful', false)
                    ->latest('attempted_at')
                    ->first();
                
                if ($lastFailedAttempt) {
                    // ИСПРАВЛЕНИЕ: Используем copy() чтобы не изменять исходный объект Carbon
                    $unblockTime = $lastFailedAttempt->attempted_at->copy()->addHours(48);
                    
                    // Проверяем, что блокировка ещё действует
                    if ($unblockTime->isFuture()) {
                        $timeLeft = $unblockTime->diffForHumans();
                        
                        return response()->view('auth.blocked', [
                            'message' => "Ваш IP заблокирован за превышение лимита попыток входа. Разблокировка через: {$timeLeft}",
                            'unblock_time' => $unblockTime
                        ], 429);
                    }
                }
            }

            // Проверяем, нужна ли капча
            if (LoginAttempt::requiresCaptcha($ip)) {
                Session::put('requires_captcha', true);
                
                $failedAttempts = LoginAttempt::getFailedAttemptsForIp($ip);
                $attemptsLeft = 8 - $failedAttempts;
                
                if ($failedAttempts >= 3 && $attemptsLeft > 0) {
                    Session::flash('warning', "Внимание! Осталось {$attemptsLeft} попыток входа, после чего IP будет заблокирован на 48 часов.");
                }
            }
        } catch (\Exception $e) {
            \Log::error('LoginAttempt error: ' . $e->getMessage());
        }

        return $next($request);
    }
}