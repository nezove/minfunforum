<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Проверяем, не заблокирован ли пользователь
        if ($user->isBanned()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Ваш аккаунт заблокирован.');
        }

        // Проверяем роль
        if (!$user->hasAnyRole($roles)) {
            abort(403, 'У вас нет прав для доступа к этой странице.');
        }

        return $next($request);
    }
}