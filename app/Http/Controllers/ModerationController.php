<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Post;
use Illuminate\Support\Facades\Log;
use App\Models\Tag;
use App\Models\User;
use App\Models\Category;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AdminActionLogger;

class ModerationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:moderator,admin']);
    }

    /**
     * Панель модерации
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'banned_users' => User::banned()->count(),
            'total_topics' => Topic::count(),
            'total_posts' => Post::count(),
            'recent_reports' => 0, // можно добавить систему жалоб позже
        ];

        $recentTopics = Topic::with(['user', 'category'])
            ->latest()
            ->limit(10)
            ->get();

        $recentPosts = Post::with(['user', 'topic'])
            ->latest()
            ->limit(10)
            ->get();

        $bannedUsers = User::banned()
            ->with('bannedByUser')
            ->latest('banned_at')
            ->limit(10)
            ->get();

        return view('moderation.index', compact('stats', 'recentTopics', 'recentPosts', 'bannedUsers'));
    }

    /**
     * Управление пользователями
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Фильтры
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'banned') {
                $query->banned();
            } elseif ($request->status === 'active') {
                $query->notBanned();
            }
        }

        $users = $query->with('bannedByUser')
                      ->orderBy('created_at', 'desc')
                      ->paginate(20);

        return view('moderation.users', compact('users'));
    }

    /**
     * Заблокировать пользователя
     */
public function banUser(Request $request, User $user)
{
    $request->validate([
        'reason' => 'required|string|max:500',
        'duration' => 'required|in:1h,24h,7d,30d,permanent',
    ]);

    // Проверки прав (как было)
    if ($user->isStaff()) {
        return back()->with('error', 'Нельзя заблокировать администратора или модератора.');
    }

    if ($user->id === auth()->id()) {
        return back()->with('error', 'Нельзя заблокировать самого себя.');
    }

    $bannedUntil = null;
    if ($request->duration !== 'permanent') {
        $bannedUntil = match($request->duration) {
            '1h' => now()->addHour(),
            '24h' => now()->addDay(),
            '7d' => now()->addWeek(),
            '30d' => now()->addMonth(),
        };
    }

    // Логируем действие
    AdminActionLogger::logUserBan(auth()->user(), $user, $request->reason, $request->duration);

    $user->ban($request->reason, $bannedUntil, auth()->user());

    // НОВОЕ: Создаём уведомление о бане
    if ($bannedUntil) {
        // Временный бан
        Notification::createTemporaryBanNotification(
            $user->id, 
            auth()->id(), 
            $request->reason, 
            $bannedUntil
        );
    } else {
        // Постоянный бан
        Notification::createPermanentBanNotification(
            $user->id, 
            auth()->id(), 
            $request->reason
        );
    }

    $durationText = $request->duration === 'permanent' 
        ? 'навсегда' 
        : 'до ' . $bannedUntil->format('d.m.Y H:i');

    return back()->with('success', "Пользователь {$user->name} заблокирован {$durationText}.");
}

    /**
     * Разблокировать пользователя
     */
    public function unbanUser(User $user)
{
    if (!$user->isBanned()) {
        return back()->with('error', 'Пользователь не заблокирован.');
    }

    // НОВОЕ: Логируем действие
    AdminActionLogger::logUserUnban(auth()->user(), $user);

    $user->unban();

    return back()->with('success', "Пользователь {$user->name} разблокирован.");
}


    /**
     * Изменить роль пользователя (только для админов)
     */
public function changeRole(Request $request, User $user)
{
    if (!auth()->user()->isAdmin()) {
        abort(403);
    }

    $request->validate([
        'role' => 'required|in:user,moderator,admin',
    ]);

    if ($user->id === auth()->id()) {
        return back()->with('error', 'Нельзя изменить роль самому себе.');
    }

    $oldRole = $user->role;
    
    // НОВОЕ: Логируем действие
    AdminActionLogger::logRoleChange(auth()->user(), $user, $oldRole, $request->role);
    
    $user->update(['role' => $request->role]);
    
    return back()->with('success', "Роль пользователя {$user->name} изменена с {$oldRole} на {$request->role}.");
}

    /**
     * Удалить тему
     */
public function deleteTopic(Request $request, Topic $topic) // <-- ДОБАВИТЬ Request $request
{
    // Валидация: причина не обязательна, но если есть - должна быть строкой
    $request->validate([
    'reason' => 'nullable|string|max:500',
    'no_notification' => 'nullable', // убираем boolean
]);

    $topicTitle = $topic->title;
    $topicId = $topic->id;
    $authorId = $topic->user_id;
    $reason = $request->reason;
    $noNotification = $request->boolean('no_notification'); // используем boolean()
    
    if (!auth()->user()->isAdmin() && $topic->user->isAdmin()) {
        return back()->with('error', 'Модератор не может удалить тему администратора.');
    }

    // НОВОЕ: Логируем действие
    AdminActionLogger::logTopicDelete(auth()->user(), $topicId, $topicTitle, $authorId);

    // НОВОЕ: Создаём уведомление автору темы только если есть причина и не выбрано "без уведомления"
    if ($authorId !== auth()->id() && !$noNotification && !empty($reason)) {
        Notification::createTopicDeletedNotification($authorId, auth()->id(), $topicTitle, $reason);
    }

    $topic->delete();

    return redirect()->route('forum.index')
                    ->with('success', "Тема \"{$topicTitle}\" удалена.");
}

    /**
     * Удалить пост
     */
public function deletePost(Request $request, Post $post) // <-- ДОБАВИТЬ Request $request
{
    // Валидация: причина не обязательна
    $request->validate([
    'reason' => 'nullable|string|max:500',
    'no_notification' => 'nullable', // убираем boolean
]);

    $postId = $post->id;
    $topicId = $post->topic_id;
    $authorId = $post->user_id;
    $reason = $request->reason;
    $noNotification = $request->boolean('no_notification'); // используем boolean()
    
    if (!auth()->user()->isAdmin() && $post->user->isAdmin()) {
        return back()->with('error', 'Модератор не может удалить пост администратора.');
    }

    // НОВОЕ: Логируем действие
    AdminActionLogger::logPostDelete(auth()->user(), $postId, $topicId, $authorId);

    // НОВОЕ: Создаём уведомление автору поста только если есть причина и не выбрано "без уведомления"
    if ($authorId !== auth()->id() && !$noNotification && !empty($reason)) {
        Notification::createPostDeletedNotification($authorId, auth()->id(), $post->content, $reason, $topicId);
    }

    // Удаляем файлы
    foreach ($post->files as $file) {
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }
    }
    
    $post->delete();

    return back()->with('success', 'Пост удален.');
}

/**
     * Переместить тему в другую категорию
     */
    public function moveTopic(Request $request, Topic $topic)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $oldCategory = $topic->category->name;
        $newCategory = Category::find($request->category_id);
        
        $topic->update(['category_id' => $request->category_id]);
// НОВОЕ: Создаём уведомление автору темы о перемещении
if ($topic->user_id !== auth()->id()) {
    Notification::createTopicMovedNotification(
        $topic->user_id, 
        auth()->id(), 
        $topic->title, 
        $oldCategory, 
        $newCategory->name,
        $topic->id
    );
}

        return back()->with('success', "Тема перемещена из \"{$oldCategory}\" в \"{$newCategory->name}\".");
    }

    /**
     * Закрыть/открыть тему
     */
    public function toggleTopicStatus(Topic $topic)
    {
        $topic->update(['is_closed' => !$topic->is_closed]);
        
        $status = $topic->is_closed ? 'закрыта' : 'открыта';
        
        return back()->with('success', "Тема \"{$topic->title}\" {$status}.");
    }
    public function changePinType(Request $request, Topic $topic)
{
    $request->validate([
        'pin_type' => 'required|in:none,category,global',
    ]);

    $oldPinType = $topic->pin_type_text;
    
    switch ($request->pin_type) {
        case 'global':
            $topic->pinGlobally();
            break;
        case 'category':
            $topic->pinInCategory();
            break;
        default:
            $topic->unpin();
            break;
    }

    $newPinType = $topic->fresh()->pin_type_text;
    
    return back()->with('success', "Статус темы изменен с \"{$oldPinType}\" на \"{$newPinType}\".");
}
/**
     * Управление категориями
     */
    public function categories()
    {
        $categories = Category::with(['tags' => function($query) {
            $query->orderBy('name');
        }])->orderBy('sort_order')->get();

        return view('moderation.categories', compact('categories'));
    }


    /**
     * Создание новой категории
     */
    public function storeCategory(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name',
        'description' => 'nullable|string|max:1000',
        'icon' => 'nullable|string|max:50',
        'seo_title' => 'nullable|string|max:255',
        'seo_description' => 'nullable|string|max:500',
        'seo_keywords' => 'nullable|string|max:500',
        'sort_order' => 'nullable|integer|min:0',
        'allow_gallery' => 'nullable|boolean', // ДОБАВЛЕНО
    ]);

    $maxSortOrder = Category::max('sort_order') ?? 0;

    Category::create([
        'name' => $request->name,
        'description' => $request->description,
        'icon' => $request->icon,
        'seo_title' => $request->seo_title,
        'seo_description' => $request->seo_description,
        'seo_keywords' => $request->seo_keywords,
        'sort_order' => $request->sort_order ?? ($maxSortOrder + 1),
        'allow_gallery' => $request->boolean('allow_gallery'), // ДОБАВЛЕНО
    ]);

    return back()->with('success', 'Категория успешно создана!');
}



    /**
     * Обновление категории
     */
    public function updateCategory(Request $request, Category $category)
{
    $request->validate([
        'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        'description' => 'nullable|string|max:1000',
        'icon' => 'nullable|string|max:50',
        'seo_title' => 'nullable|string|max:255',
        'seo_description' => 'nullable|string|max:500',
        'seo_keywords' => 'nullable|string|max:500',
        'sort_order' => 'nullable|integer|min:0',
        'allow_gallery' => 'nullable|boolean', // ДОБАВЛЕНО
    ]);

    $category->update($request->only([
        'name', 'description', 'icon', 'seo_title', 
        'seo_description', 'seo_keywords', 'sort_order', 'allow_gallery' // ДОБАВЛЕНО allow_gallery
    ]));

    return back()->with('success', 'Категория успешно обновлена!');
}


    /**
     * Удаление категории
     */
    public function deleteCategory(Category $category)
    {
        // Проверяем, есть ли темы в категории
        if ($category->topics()->count() > 0) {
            return back()->with('error', 'Нельзя удалить категорию, содержащую темы!');
        }

        $category->delete();

        return back()->with('success', 'Категория успешно удалена!');
    }


    /**
     * Создание нового тега
     */
    public function storeTag(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
        ], [
            'color.regex' => 'Цвет должен быть в формате #FFFFFF',
        ]);

        Tag::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? '#007bff',
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'seo_keywords' => $request->seo_keywords,
            'is_active' => true,
        ]);

        return back()->with('success', 'Тег успешно создан!');
    }


    /**
     * Обновление тега
     */
    public function updateTag(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'color.regex' => 'Цвет должен быть в формате #FFFFFF',
            'name.required' => 'Название тега обязательно для заполнения',
            'name.max' => 'Название тега не может быть длиннее 100 символов',
        ]);

        $oldName = $tag->name;
        $wasActive = $tag->is_active;

        $tag->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? $tag->color,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'seo_keywords' => $request->seo_keywords,
            'is_active' => $request->has('is_active'),
        ]);

        $message = "Тег успешно обновлен!";
        
        // Информируем об изменении статуса
        if ($wasActive && !$tag->is_active) {
            $message .= " Тег деактивирован и больше не будет отображаться пользователям.";
        } elseif (!$wasActive && $tag->is_active) {
            $message .= " Тег активирован и теперь доступен пользователям.";
        }

        return back()->with('success', $message);
    }


    /**
     * Удаление тега
     */
    public function deleteTag(Tag $tag)
    {
        $tagName = $tag->name;
        $topicsCount = $tag->topics()->count();
        
        // Отвязываем тег от всех тем
        $tag->topics()->detach();
        
        // Удаляем тег
        $tag->delete();

        $message = "Тег \"{$tagName}\" успешно удален";
        if ($topicsCount > 0) {
            $message .= " и убран из {$topicsCount} тем";
        }

        return back()->with('success', $message . '!');
    }
/**
 * Получить детальную активность пользователя (ТОЛЬКО ДЛЯ АДМИНОВ!)
 */
public function getUserActivity(Request $request, User $user)
{
    // ДВОЙНАЯ ПРОВЕРКА: Только админы могут видеть активность!
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Доступ запрещен. Только для администраторов.');
    }

    try {
        // Получаем сессии пользователя
        $sessions = \App\Services\SessionLogger::getUserSessions($user->id, 20);
        
        // Получаем подозрительную активность
        $suspiciousActivity = \App\Services\SessionLogger::checkSuspiciousActivity($user->id);
        
        // Получаем последние темы
        $recentTopics = $user->topics()
            ->with('category')
            ->latest()
            ->limit(10)
            ->get();
            
        // Получаем последние посты
        $recentPosts = $user->posts()
            ->with(['topic', 'topic.category'])
            ->latest()
            ->limit(15)
            ->get();
            
        // Получаем лайки
        $recentLikes = $user->likes()
            ->with(['likeable'])
            ->latest()
            ->limit(10)
            ->get();
            
        // Получаем уведомления пользователя (последние 10)
        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get();
            
        // Статистика активности
        $stats = [
            'total_topics' => $user->topics_count,
            'total_posts' => $user->posts_count,
            'total_likes_given' => $user->likes()->count(),
            'total_likes_received' => \App\Models\Like::whereHasMorph('likeable', [\App\Models\Topic::class, \App\Models\Post::class], function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'registration_date' => $user->created_at,
            'last_activity' => $user->last_activity_at,
            'account_age_days' => $user->created_at->diffInDays(now()),
            'is_banned' => $user->isBanned(),
            'ban_info' => $user->isBanned() ? $user->getBanInfo() : null,
        ];

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'avatar_url' => $user->avatar_url,
            ],
            'stats' => $stats,
            'sessions' => $sessions,
            'suspicious_activity' => $suspiciousActivity,
            'recent_topics' => $recentTopics,
            'recent_posts' => $recentPosts,
            'recent_likes' => $recentLikes,
            'notifications' => $notifications,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Ошибка при загрузке данных активности пользователя.',
            'message' => $e->getMessage()
        ], 500);
    }
}
/**
 * Массово удалить все темы пользователя (ТОЛЬКО ДЛЯ АДМИНОВ!)
 */
public function deleteAllUserTopics(Request $request, User $user)
{
    // ДВОЙНАЯ ПРОВЕРКА: Только админы могут массово удалять!
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Доступ запрещен. Только для администраторов.');
    }

    $request->validate([
        'reason' => 'nullable|string|max:500',
        'confirm_username' => 'required|string',
    ]);

    // Проверка подтверждения имени пользователя
    if ($request->confirm_username !== $user->username) {
        return response()->json([
            'success' => false,
            'message' => 'Неверное подтверждение имени пользователя.'
        ], 422);
    }

    // Нельзя удалять темы самого себя
    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Нельзя удалять свои собственные темы через эту функцию.'
        ], 422);
    }

    try {
        $topicsCount = $user->topics()->count();
        
        if ($topicsCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'У пользователя нет тем для удаления.'
            ], 422);
        }

        // Логируем действие
        AdminActionLogger::logAction(auth()->user(), 'MASS_DELETE_USER_TOPICS', [
            'target_user_id' => $user->id,
            'target_username' => $user->username,
            'topics_count' => $topicsCount,
            'reason' => $request->reason,
        ]);

        // Удаляем все темы пользователя
        $user->topics()->delete();

        // Обновляем счетчик
        $user->update(['topics_count' => 0]);

        return response()->json([
            'success' => true,
            'message' => "Удалено {$topicsCount} тем пользователя {$user->username}."
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to delete user topics', [
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при удалении тем: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Массово удалить все посты пользователя (ТОЛЬКО ДЛЯ АДМИНОВ!)
 */
public function deleteAllUserPosts(Request $request, User $user)
{
    // ДВОЙНАЯ ПРОВЕРКА: Только админы могут массово удалять!
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Доступ запрещен. Только для администраторов.');
    }

    $request->validate([
        'reason' => 'nullable|string|max:500',
        'confirm_username' => 'required|string',
    ]);

    // Проверка подтверждения имени пользователя
    if ($request->confirm_username !== $user->username) {
        return response()->json([
            'success' => false,
            'message' => 'Неверное подтверждение имени пользователя.'
        ], 422);
    }

    // Нельзя удалять посты самого себя
    if ($user->id === auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => 'Нельзя удалять свои собственные посты через эту функцию.'
        ], 422);
    }

    try {
        $postsCount = $user->posts()->count();
        
        if ($postsCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'У пользователя нет постов для удаления.'
            ], 422);
        }

        // Логируем действие
        AdminActionLogger::logAction(auth()->user(), 'MASS_DELETE_USER_POSTS', [
            'target_user_id' => $user->id,
            'target_username' => $user->username,
            'posts_count' => $postsCount,
            'reason' => $request->reason,
        ]);

        // Удаляем все посты пользователя
        $user->posts()->delete();

        // Обновляем счетчик
        $user->update(['posts_count' => 0]);

        // Обновляем счетчики ответов в темах
        DB::statement('
            UPDATE topics t 
            SET replies_count = (
                SELECT COUNT(*) - 1 
                FROM posts p 
                WHERE p.topic_id = t.id
            )
        ');

        return response()->json([
            'success' => true,
            'message' => "Удалено {$postsCount} постов пользователя {$user->username}."
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to delete user posts', [
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при удалении постов: ' . $e->getMessage()
        ], 500);
    }
}

}