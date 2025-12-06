<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Topic;
use App\Models\User;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemaps = [
            ['loc' => route('sitemap.main'), 'lastmod' => now()->toW3CString()],
            ['loc' => route('sitemap.categories'), 'lastmod' => now()->toW3CString()],
            ['loc' => route('sitemap.topics'), 'lastmod' => $this->getLastTopicUpdate()],
        ];

        return response()->view('sitemap.index', compact('sitemaps'))
            ->header('Content-Type', 'application/xml');
    }

    public function main()
    {
        $urls = [
            [
                'loc' => url('/'),
                'lastmod' => now()->toW3CString(),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => route('login'),
                'lastmod' => now()->toW3CString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('register'),
                'lastmod' => now()->toW3CString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('terms'),
                'lastmod' => now()->toW3CString(),
                'changefreq' => 'yearly',
                'priority' => '0.2'
            ],
        ];

        return response()->view('sitemap.urlset', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    public function categories()
    {
        $categories = Category::all();
        $urls = [];

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('forum.category', $category->id),
                'lastmod' => $category->updated_at->toW3CString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];

            // Добавляем страницы с тегами для каждой категории
            $tags = Tag::where('category_id', $category->id)->where('is_active', true)->get();
            foreach ($tags as $tag) {
                $urls[] = [
                    'loc' => route('tags.show', ['category' => $category->id, 'tag' => $tag->slug]),
                    'lastmod' => $tag->updated_at->toW3CString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6'
                ];
            }
        }

        return response()->view('sitemap.urlset', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    public function topics(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = 5000; // Google рекомендует не более 50000 URL в одном sitemap
        $offset = ($page - 1) * $limit;

        $topics = Topic::select('id', 'updated_at', 'created_at', 'views_count', 'replies_count')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $urls = [];
        foreach ($topics as $topic) {
            // Определяем приоритет на основе активности темы
            $priority = $this->calculateTopicPriority($topic);
            
            $urls[] = [
                'loc' => route('topics.show', $topic->id),
                'lastmod' => $topic->updated_at->toW3CString(),
                'changefreq' => $this->getTopicChangeFreq($topic),
                'priority' => $priority
            ];
        }

        return response()->view('sitemap.urlset', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Вычисляет приоритет темы на основе активности
     */
    private function calculateTopicPriority($topic)
    {
        $base = 0.5;
        
        // Бонус за просмотры
        if ($topic->views_count > 1000) $base += 0.2;
        elseif ($topic->views_count > 100) $base += 0.1;
        
        // Бонус за ответы
        if ($topic->replies_count > 50) $base += 0.2;
        elseif ($topic->replies_count > 10) $base += 0.1;
        
        // Бонус за свежесть
        if ($topic->updated_at->diffInDays() < 7) $base += 0.1;
        
        return min(1.0, $base);
    }

    /**
     * Определяет частоту изменения темы
     */
    private function getTopicChangeFreq($topic)
    {
        $daysSinceUpdate = $topic->updated_at->diffInDays();
        
        if ($daysSinceUpdate < 1) return 'hourly';
        if ($daysSinceUpdate < 7) return 'daily';
        if ($daysSinceUpdate < 30) return 'weekly';
        if ($daysSinceUpdate < 90) return 'monthly';
        
        return 'yearly';
    }

    /**
     * Получает дату последнего обновления темы
     */
    private function getLastTopicUpdate()
    {
        $lastTopic = Topic::latest('updated_at')->first();
        return $lastTopic ? $lastTopic->updated_at->toW3CString() : now()->toW3CString();
    }
}