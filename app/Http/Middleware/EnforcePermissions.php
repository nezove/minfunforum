<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforcePermissions
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        
        // Проверяем бан
        if ($user->isBanned()) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Аккаунт заблокирован');
        }
        
        // Проверяем права
        switch ($permission) {
            case 'create_post':
                if (!$user->canPerformActions()) {
                    abort(403, 'Нет прав для создания постов');
                }
                break;
            case 'upload_file':
                if (!$user->canPerformActions()) {
                    abort(403, 'Нет прав для загрузки файлов');
                }
                break;
            case 'moderate':
                if (!$user->canModerate()) {
                    abort(403, 'Нет прав модератора');
                }
                break;
        }
        
        return $next($request);
    }
}