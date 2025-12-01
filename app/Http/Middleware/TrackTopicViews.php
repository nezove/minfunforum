<?php

// php artisan make:middleware TrackTopicViews

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Topic;
use Illuminate\Support\Facades\Cache;

class TrackTopicViews
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Проверяем, что это страница темы
        if ($request->route() && $request->route()->getName() === 'topics.show') {
            $topicId = $request->route()->parameter('topic') ?? $request->route()->parameter('id');
            
            if ($topicId) {
                $this->trackView($topicId, $request);
            }
        }
        
        return $response;
    }

    private function trackView($topicId, Request $request)
    {
        // Создаем уникальный ключ для пользователя/IP
        $userKey = auth()->id() ?? $request->ip();
        $viewKey = "topic_view_{$topicId}_{$userKey}";
        
        // Проверяем, не засчитывали ли уже просмотр за последний час
        if (!Cache::has($viewKey)) {
            try {
                $topic = Topic::find($topicId);
                if ($topic) {
                    // ИСПРАВЛЕНО: используем правильное поле 'views' вместо 'views_count'
                    $topic->increment('views');
                    
                    // Помечаем, что пользователь посмотрел эту тему (на 1 час)
                    Cache::put($viewKey, true, 3600);
                }
            } catch (\Exception $e) {
                \Log::error('Error tracking topic view: ' . $e->getMessage());
            }
        }
    }
}