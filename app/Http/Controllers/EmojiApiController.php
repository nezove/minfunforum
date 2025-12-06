<?php

namespace App\Http\Controllers;

use App\Models\Emoji;
use App\Models\EmojiCategory;
use Illuminate\Http\Request;

class EmojiApiController extends Controller
{
    /**
     * Получить все категории с активными смайликами
     */
    public function getCategories()
    {
        $categories = EmojiCategory::active()
            ->ordered()
            ->with(['activeEmojis' => function($query) {
                $query->ordered();
            }])
            ->get()
            ->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'emojis' => $category->activeEmojis->map(function($emoji) {
                        return [
                            'id' => $emoji->id,
                            'name' => $emoji->name,
                            'url' => $emoji->file_url,
                            'keywords' => $emoji->keywords_array,
                            'width' => $emoji->width,
                            'height' => $emoji->height,
                            'file_type' => $emoji->file_type,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Поиск смайликов по ключевому слову
     */
    public function search(Request $request)
    {
        $keyword = $request->input('q', '');

        if (strlen($keyword) < 2) {
            return response()->json([
                'success' => true,
                'emojis' => [],
            ]);
        }

        $emojis = Emoji::active()
            ->searchByKeyword($keyword)
            ->ordered()
            ->limit(20)
            ->get()
            ->map(function($emoji) {
                return [
                    'id' => $emoji->id,
                    'name' => $emoji->name,
                    'url' => $emoji->file_url,
                    'keywords' => $emoji->keywords_array,
                    'width' => $emoji->width,
                    'height' => $emoji->height,
                    'file_type' => $emoji->file_type,
                    'category' => $emoji->category->name,
                ];
            });

        return response()->json([
            'success' => true,
            'emojis' => $emojis,
        ]);
    }

    /**
     * Получить популярные смайлики
     */
    public function popular()
    {
        $emojis = Emoji::active()
            ->popular(20)
            ->get()
            ->map(function($emoji) {
                return [
                    'id' => $emoji->id,
                    'name' => $emoji->name,
                    'url' => $emoji->file_url,
                    'keywords' => $emoji->keywords_array,
                    'width' => $emoji->width,
                    'height' => $emoji->height,
                    'file_type' => $emoji->file_type,
                    'usage_count' => $emoji->usage_count,
                ];
            });

        return response()->json([
            'success' => true,
            'emojis' => $emojis,
        ]);
    }

    /**
     * Увеличить счетчик использования смайлика
     */
    public function incrementUsage(Request $request)
    {
        $emojiId = $request->input('emoji_id');
        $emoji = Emoji::find($emojiId);

        if ($emoji) {
            $emoji->incrementUsage();

            return response()->json([
                'success' => true,
                'message' => 'Usage count incremented',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Emoji not found',
        ], 404);
    }
}
