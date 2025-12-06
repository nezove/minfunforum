<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Category;
use App\Helpers\SeoHelper;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Показать темы по тегу
     */
    public function show(Request $request, $categoryId, $tagSlug)
    {
        $category = Category::findOrFail($categoryId);
        $tag = Tag::where('slug', $tagSlug)
                  ->where('category_id', $categoryId)
                  ->where('is_active', true)
                  ->firstOrFail();

        // Получаем темы с этим тегом
        $topics = $tag->topics()
            ->with(['user', 'lastPost.user'])
            ->orderByRaw("
                CASE 
                    WHEN pin_type = 'global' THEN 1 
                    WHEN pin_type = 'category' THEN 2 
                    ELSE 3 
                END
            ")
            ->latest('last_activity_at')
            ->paginate(20);

        // SEO данные
        $siteName = config('app.name', 'Forum');
        $seoTitle = $tag->seo_title ?: SeoHelper::generateTagTitle(
            $tag->name, 
            $category->name, 
            $topics->total(), 
            $siteName
        );
        $seoDescription = $tag->seo_description ?: SeoHelper::generateTagDescription(
            $tag->name,
            $category->name,
            $tag->description,
            $topics->total()
        );
        $seoKeywords = $tag->seo_keywords ?: SeoHelper::generateKeywords([
            $tag->name,
            $category->name,
            'форум',
            'обсуждение',
            'разработка'
        ]);

        return view('tags.show', compact(
            'category', 
            'tag', 
            'topics', 
            'seoTitle', 
            'seoDescription', 
            'seoKeywords'
        ));
    }

    /**
     * API для получения тегов категории (для AJAX)
     */
    public function getTagsByCategory($categoryId)
    {
        $tags = Tag::where('category_id', $categoryId)
                   ->where('is_active', true)
                   ->orderBy('name')
                   ->get(['id', 'name', 'color']);

        return response()->json($tags);
    }
}