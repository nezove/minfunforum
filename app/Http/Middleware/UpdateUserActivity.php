<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $cacheKey = 'user_activity_' . $user->id;
            
            // Обновляем активность не чаще раза в 2 минуты для оптимизации
            if (!Cache::has($cacheKey)) {
                try {
                    // Обновляем время последней активности напрямую в БД
                    \DB::table('users')
                        ->where('id', $user->id)
                        ->update(['last_activity_at' => now()]);
                    
                    // Кешируем на 2 минуты, чтобы не делать UPDATE на каждый запрос
                    Cache::put($cacheKey, true, 120);
                } catch (\Exception $e) {
                    // Логируем ошибку, но не ломаем запрос
                    \Log::error('Error updating user activity: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }
}