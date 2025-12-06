<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Если пользователь не авторизован и пытается просмотреть тему
        if (!Auth::check() && $request->route()->getName() === 'topics.show') {
            // Сохраняем URL темы в сессии
            session(['intended_url' => $request->url()]);
        }

        return $next($request);
    }
}