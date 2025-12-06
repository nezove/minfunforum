<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SitemapCache
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
        // Создаем уникальный ключ кеша на основе URL и параметров
        $cacheKey = 'sitemap_' . md5($request->getUri());
        
        // Проверяем кеш
        if (Cache::has($cacheKey)) {
            $cachedContent = Cache::get($cacheKey);
            
            return Response::make($cachedContent, 200, [
                'Content-Type' => 'application/xml',
                'Cache-Control' => 'public, max-age=3600', // Кеш на 1 час
            ]);
        }
        
        // Выполняем запрос
        $response = $next($request);
        
        // Кешируем ответ на 1 час (3600 секунд)
        if ($response->getStatusCode() === 200) {
            Cache::put($cacheKey, $response->getContent(), 3600);
            
            // Добавляем заголовки кеширования
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('ETag', '"' . md5($response->getContent()) . '"');
        }
        
        return $response;
    }
}