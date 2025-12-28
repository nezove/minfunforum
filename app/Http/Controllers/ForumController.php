<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    public function index()
    {
        $categories = Category::with(['topics' => function($query) {
            $query->latest('last_activity_at')->limit(5);
        }])->get();

        $latestTopics = Topic::with(['user', 'category', 'lastPost.user'])
        ->orderByRaw("
            CASE 
                WHEN pin_type = 'global' THEN 1 
                ELSE 2 
            END
        ")
        ->latest('last_activity_at')
        ->limit(10)
        ->get();

        // ИСПРАВЛЕНИЕ: Активные пользователи с правильным подсчётом и сортировкой по лайкам
        $activeUsers = $this->getActiveUsers();

        // Проверяем, есть ли непрочитанные темы
        $hasUnreadTopics = false;
        if (auth()->check()) {
            foreach ($latestTopics as $topic) {
                if (!$topic->isReadBy(auth()->user())) {
                    $hasUnreadTopics = true;
                    break;
                }
            }
        }

        // SEO данные
        $siteName = config('app.name', 'Forum');
        $seoTitle = SeoHelper::generateHomeTitle($siteName);
        $seoDescription = SeoHelper::generateHomeDescription(
            Topic::count(),
            User::count()
        );
        $seoKeywords = SeoHelper::generateKeywords([
            'форум разработчиков',
            'программирование',
            'веб-разработка',
            'IT сообщество',
            'Laravel',
            'PHP',
            'JavaScript'
        ]);

        return view('forum.index', compact(
            'categories',
            'latestTopics',
            'activeUsers', // Добавляем активных пользователей
            'hasUnreadTopics', // Флаг наличия непрочитанных тем
            'seoTitle',
            'seoDescription',
            'seoKeywords'
        ));
    }

    /**
     * Получить активных пользователей с правильным подсчётом
     */
    private function getActiveUsers()
{
    // Получаем активных пользователей с правильными счётчиками
    $activeUsers = User::select('id', 'username', 'name', 'avatar', 'role', 'last_activity_at', 'posts_count', 'topics_count')
        ->where('last_activity_at', '>=', now()->subDays(30))
        ->whereHas('posts') // У кого есть хотя бы один пост
        ->get()
        ->map(function($user) {
            // Пересчитываем реальное количество постов
            $realPostsCount = $user->posts()->count();
            
            // Считаем лайки на посты пользователя
            $likesOnPosts = DB::table('likes')
                ->join('posts', 'likes.likeable_id', '=', 'posts.id')
                ->where('likes.likeable_type', 'App\\Models\\Post')
                ->where('posts.user_id', $user->id)
                ->where('likes.created_at', '>=', now()->subDays(30))
                ->count();
            
            // Считаем лайки на темы пользователя
            $likesOnTopics = DB::table('likes')
                ->join('topics', 'likes.likeable_id', '=', 'topics.id')
                ->where('likes.likeable_type', 'App\\Models\\Topic')
                ->where('topics.user_id', $user->id)
                ->where('likes.created_at', '>=', now()->subDays(30))
                ->count();
            
            $totalLikes = $likesOnPosts + $likesOnTopics;
            
            // Обновляем счётчик постов если неправильный
            if ($user->posts_count !== $realPostsCount) {
                $user->update(['posts_count' => $realPostsCount]);
            }
            
            $daysSinceActivity = $user->last_activity_at ? 
                now()->diffInDays($user->last_activity_at) : 30;
            
            // Формула: лайки * 5 + посты * 1 + бонус за недавнюю активность
            $activityScore = ($totalLikes * 5) + ($realPostsCount * 1) + max(0, (30 - $daysSinceActivity));
            
            return [
                'user' => $user,
                'posts_count' => $realPostsCount,
                'likes_received' => $totalLikes,
                'recent_activity' => $user->last_activity_at,
                'activity_score' => $activityScore
            ];
        })
        ->sortByDesc('activity_score')
        ->take(10);

    return $activeUsers;
}

    public function category(Request $request, $id)
    {
        $category = Category::with('tagsWithTopics')->findOrFail($id);
        
        $query = Topic::where('category_id', $id)->with(['user', 'lastPost.user', 'tags']);

        // Фильтрация по тегам
        if ($request->has('tags') && !empty($request->tags)) {
            $tagSlugs = is_array($request->tags) ? $request->tags : [$request->tags];
            
            $query->whereHas('tags', function($q) use ($tagSlugs, $id) {
                $q->whereIn('slug', $tagSlugs)->where('category_id', $id);
            });
        }

        $topics = $query->orderByRaw("
            CASE 
                WHEN pin_type = 'global' THEN 1 
                WHEN pin_type = 'category' THEN 2 
                ELSE 3 
            END
        ")
        ->latest('last_activity_at')
        ->paginate(20);

        // Получаем выбранные теги для отображения
        $selectedTags = collect();
        if ($request->has('tags') && !empty($request->tags)) {
            $tagSlugs = is_array($request->tags) ? $request->tags : [$request->tags];
            $selectedTags = Tag::whereIn('slug', $tagSlugs)
                              ->where('category_id', $id)
                              ->where('is_active', true)
                              ->get();
        }

        // SEO данные
        $siteName = config('app.name', 'Forum');
        $seoTitle = $category->seo_title ?: SeoHelper::generateCategoryTitle(
            $category->name, 
            $topics->total(), 
            $siteName
        );
        $seoDescription = $category->seo_description ?: SeoHelper::generateCategoryDescription(
            $category->name,
            $category->description ?? '',
            $topics->total()
        );
        $seoKeywords = SeoHelper::generateKeywords([
            $category->name,
            'форум',
            'обсуждения',
            'сообщество'
        ]);

        return view('forum.category', compact(
            'category', 
            'topics', 
            'selectedTags',
            'seoTitle', 
            'seoDescription', 
            'seoKeywords'
        ));
    }
}