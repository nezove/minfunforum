<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, topics, posts, users
        $category = $request->get('category', '');
        $sort = $request->get('sort', 'relevance'); // relevance, date, likes, views
        $dateFilter = $request->get('date', ''); // week, month, year
        
        $results = collect();
        $totalResults = 0;
        
        if (strlen($query) >= 2) {
            switch ($type) {
                case 'topics':
                    $results = $this->searchTopics($query, $category, $sort, $dateFilter);
                    break;
                case 'posts':
                    $results = $this->searchPosts($query, $category, $sort, $dateFilter);
                    break;
                case 'users':
                    $results = $this->searchUsers($query, $sort);
                    break;
                default:
                    $results = $this->searchAll($query, $category, $sort, $dateFilter);
                    break;
            }
            $totalResults = $results->total();
        }
        
        $categories = Category::orderBy('name')->get();
        
        return view('search.index', compact(
            'query', 
            'type', 
            'category', 
            'sort', 
            'dateFilter',
            'results', 
            'totalResults', 
            'categories'
        ));
    }
    
    private function searchTopics($query, $category, $sort, $dateFilter)
    {
        $builder = Topic::with(['user', 'category', 'lastPost.user'])
            ->where(function (Builder $q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });
            
        return $this->applyFiltersAndSort($builder, $category, $sort, $dateFilter);
    }
    
    private function searchPosts($query, $category, $sort, $dateFilter)
    {
        $builder = Post::with(['user', 'topic.category'])
            ->where('content', 'LIKE', "%{$query}%")
            ->whereHas('topic', function (Builder $q) {
                $q->whereNull('deleted_at');
            });
            
        if ($category) {
            $builder->whereHas('topic.category', function (Builder $q) use ($category) {
                $q->where('id', $category);
            });
        }
        
        return $this->applySortAndDate($builder, $sort, $dateFilter, 'post');
    }
    
    private function searchUsers($query, $sort)
    {
        return User::where(function (Builder $q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('username', 'LIKE', "%{$query}%");
            })
            ->when($sort === 'posts', function (Builder $q) {
                return $q->orderBy('posts_count', 'desc');
            })
            ->when($sort === 'topics', function (Builder $q) {
                return $q->orderBy('topics_count', 'desc');
            })
            ->when($sort === 'date', function (Builder $q) {
                return $q->orderBy('created_at', 'desc');
            })
            ->orderBy('name')
            ->paginate(20);
    }
    
    private function searchAll($query, $category, $sort, $dateFilter)
    {
        // Для "все" возвращаем только темы, но с более широким поиском
        $builder = Topic::with(['user', 'category', 'lastPost.user'])
            ->where(function (Builder $q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%")
                  ->orWhereHas('posts', function (Builder $subQ) use ($query) {
                      $subQ->where('content', 'LIKE', "%{$query}%");
                  });
            });
            
        return $this->applyFiltersAndSort($builder, $category, $sort, $dateFilter);
    }
    
    private function applyFiltersAndSort($builder, $category, $sort, $dateFilter)
    {
        if ($category) {
            $builder->where('category_id', $category);
        }
        
        return $this->applySortAndDate($builder, $sort, $dateFilter, 'topic');
    }
    
    private function applySortAndDate($builder, $sort, $dateFilter, $type)
    {
        // Применяем фильтр по дате
        if ($dateFilter) {
            switch ($dateFilter) {
                case 'week':
                    $builder->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $builder->where('created_at', '>=', now()->subMonth());
                    break;
                case 'year':
                    $builder->where('created_at', '>=', now()->subYear());
                    break;
            }
        }
        
        // Применяем сортировку
        switch ($sort) {
            case 'date':
                $builder->orderBy('created_at', 'desc');
                break;
            case 'likes':
                if ($type === 'topic') {
                    $builder->orderBy('likes_count', 'desc');
                }
                break;
            case 'views':
                if ($type === 'topic') {
                    $builder->orderBy('views', 'desc');
                }
                break;
            case 'replies':
                if ($type === 'topic') {
                    $builder->orderBy('replies_count', 'desc');
                }
                break;
            default: // relevance
                // Для релевантности сортируем по последней активности
                if ($type === 'topic') {
                    $builder->orderBy('last_activity_at', 'desc');
                } else {
                    $builder->orderBy('created_at', 'desc');
                }
                break;
        }
        
        return $builder->paginate(15);
    }
}