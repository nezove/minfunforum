<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfBannedMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // НОВОЕ: Автоматическая проверка истечения временного бана
            if ($user->is_banned && $user->banned_until && $user->banned_until->isPast()) {
                // Удаляем уведомления о блокировке ПЕРЕД разблокировкой
                $user->notifications()
                    ->whereIn('type', ['temporary_ban', 'permanent_ban'])
                    ->delete();
                
                $user->unban();
                
                // Очищаем предупреждение из сессии
                $request->session()->forget('ban_warning_shown');
                $request->session()->forget('ban_warning');
                
                // Добавляем уведомление о разблокировке
                $request->session()->flash('success', 'Ваш аккаунт разблокирован. Добро пожаловать обратно!');
                
                // Перезагружаем пользователя из базы данных
                auth()->setUser($user->fresh());
            }
            
            // Если пользователь заблокирован
            if ($user->isBanned()) {
                $banType = $user->getBanType();
                
                if ($banType === 'permanent') {
                    // Постоянный бан - полный выход
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован навсегда.');
                }
                
                // Временный бан - ограниченный доступ
                $allowedRoutes = [
                    'profile.show',
                    'profile.edit', 
                    'logout',
                    'banned',
                    'login',
                    'home',
                    'forum.index',
                    'forum.category',
                    'topics.show',
                    'bookmarks.index',
                    'topics.bookmark',
                    'notifications.index',
                    'notifications.show',
                    'notifications.markAsRead',
                    'notifications.markAllAsRead',
                    'notifications.deleteAll',
                    'notifications.destroy',
                    'notifications.unreadCount',
                    'notifications.markDropdownViewed',
                    'user.ban-status'
                ];

                $currentRoute = $request->route()?->getName();
                
                if (!in_array($currentRoute, $allowedRoutes)) {
                    return redirect()->route('banned');
                }
                
                // Добавляем предупреждение для временно заблокированных
                if (!session()->has('ban_warning_shown')) {
                    $timeRemaining = $user->getBanTimeRemaining();
                    $message = "Ваш аккаунт временно ограничен до {$user->banned_until->format('d.m.Y H:i')} ({$timeRemaining})";
                    if ($user->ban_reason) {
                        $message .= ". Причина: {$user->ban_reason}";
                    }
                    session()->flash('ban_warning', $message);
                    session()->put('ban_warning_shown', true);
                }
            } else {
                // НОВОЕ: Если пользователь не заблокирован, очищаем предупреждения
                if (session()->has('ban_warning_shown')) {
                    $request->session()->forget('ban_warning_shown');
                    $request->session()->forget('ban_warning');
                }
            }
        }

        return $next($request);
    }
}