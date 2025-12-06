<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Topic;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function toggle(Request $request, Topic $topic)
    {
        $result = Bookmark::toggle(auth()->id(), $topic->id);
        
        if ($request->expectsJson()) {
            return response()->json($result);
        }
        
        $message = $result['bookmarked'] ? 'Тема добавлена в закладки' : 'Тема удалена из закладок';
        return back()->with('success', $message);
    }

    public function index()
    {
        $bookmarks = auth()->user()->bookmarks()
            ->with(['topic.user', 'topic.category'])
            ->latest()
            ->paginate(20);

        return view('bookmarks.index', compact('bookmarks'));
    }
}