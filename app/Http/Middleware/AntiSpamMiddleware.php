<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\StopForumSpamService;
use Symfony\Component\HttpFoundation\Response;

class AntiSpamMiddleware
{
    private $stopForumSpamService;

    public function __construct(StopForumSpamService $stopForumSpamService)
    {
        $this->stopForumSpamService = $stopForumSpamService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем только запросы к регистрации
        if ($request->routeIs('register') && $request->isMethod('GET')) {
            $userIp = StopForumSpamService::getUserIp();
            
            if ($this->stopForumSpamService->checkIp($userIp)) {
                // IP в черном списке - блокируем доступ к форме регистрации
                return response()->view('auth.registration-blocked', [
                    'message' => 'Регистрация недоступна для вас.'
                ], 403);
            }
        }

        return $next($request);
    }
}