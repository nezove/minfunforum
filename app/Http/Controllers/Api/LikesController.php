<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Topic;
use App\Models\Post;
use Illuminate\Http\Request;

class LikesController extends Controller
{
    /**
     * Получить последние 5 лайков для предпросмотра
     */
    public function preview(Request $request)
    {
        $request->validate([
            'type' => 'required|in:topic,post',
            'id' => 'required|integer'
        ]);

        $type = $request->type === 'topic' ? Topic::class : Post::class;
        $id = $request->id;

        $likes = Like::where('likeable_type', $type)
            ->where('likeable_id', $id)
            ->with('user:id,username,avatar')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($like) {
                return [
                    'id' => $like->user->id,
                    'username' => $like->user->username,
                    'avatar_url' => $like->user->avatar_url,
                    'created_at' => $like->created_at->diffForHumans()
                ];
            });

        $totalCount = Like::where('likeable_type', $type)
            ->where('likeable_id', $id)
            ->count();

        return response()->json([
            'likes' => $likes,
            'total' => $totalCount,
            'hasMore' => $totalCount > 5
        ]);
    }

    /**
     * Получить полный список лайков с пагинацией и поиском
     */
    public function list(Request $request)
    {
        $request->validate([
            'type' => 'required|in:topic,post',
            'id' => 'required|integer',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:100'
        ]);

        $type = $request->type === 'topic' ? Topic::class : Post::class;
        $id = $request->id;
        $perPage = 20;
        $search = $request->search;

        $query = Like::where('likeable_type', $type)
            ->where('likeable_id', $id)
            ->with('user:id,username,avatar');

        // Поиск по никнейму
        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'LIKE', '%' . $search . '%');
            });
        }

        $likes = $query->latest()->paginate($perPage);

        return response()->json([
            'likes' => $likes->map(function ($like) {
                return [
                    'id' => $like->user->id,
                    'username' => $like->user->username,
                    'avatar_url' => $like->user->avatar_url,
                    'created_at' => $like->created_at->diffForHumans(),
                    'profile_url' => route('profile.show', $like->user->id)
                ];
            }),
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'last_page' => $likes->lastPage(),
                'per_page' => $likes->perPage(),
                'total' => $likes->total(),
                'has_more' => $likes->hasMorePages()
            ]
        ]);
    }
}
