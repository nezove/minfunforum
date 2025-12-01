<?php

use App\Http\Controllers\ForumController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

// Применяем антиспам middleware только к странице регистрации
Route::middleware(['antispam'])->group(function () {
    Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
});

// Остальные маршруты аутентификации без middleware
Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
// Найдите строки с login и добавьте middleware:
Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('bruteforce.protection'); // ДОБАВЬТЕ ЭТУ СТРОКУ

Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login'])
    ->middleware('bruteforce.protection'); // ДОБАВЬТЕ ЭТУ СТРОКУ

// И для регистрации:
Route::middleware(['antispam', 'bruteforce.protection'])->group(function () {
    Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
});

Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])
    ->middleware('bruteforce.protection'); // ДОБАВЬТЕ ЭТУ СТРОКУ

Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Восстановление пароля
Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');

// Подтверждение email
Route::get('email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->name('verification.verify');
Route::post('email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend');


// Публичные маршруты (без проверки на бан)
Route::get('/', [ForumController::class, 'index'])->name('forum.index');
Route::get('/home', [ForumController::class, 'index'])->name('home');
Route::get('/category/{id}', [ForumController::class, 'category'])->name('forum.category');
Route::get('/search', [App\Http\Controllers\SearchController::class, 'index'])->name('search');
Route::get('/check-username', [RegisterController::class, 'checkUsername'])
    ->name('check.username')
    ->middleware('throttle:10,1');
// Sitemap маршруты с кешированием
Route::group(['middleware' => 'sitemap.cache'], function() {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-main.xml', [SitemapController::class, 'main'])->name('sitemap.main');
    Route::get('/sitemap-categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');
    Route::get('/sitemap-topics.xml', [SitemapController::class, 'topics'])->name('sitemap.topics');
});
// Просмотр профилей (без проверки на бан)
Route::get('/user/{id}', [ProfileController::class, 'show'])->name('profile.show');
Route::get('/files/{hashedId}/download', [FileController::class, 'download'])->name('file.download');
// Страницы тегов
Route::get('/categories/{category}/tags/{tag}', [App\Http\Controllers\TagController::class, 'show'])->name('tags.show');

// Специальные страницы для забаненных
Route::get('/banned', [App\Http\Controllers\BannedController::class, 'index'])->name('banned');
Route::get('/terms', function () {
    return view('terms');
})->name('terms');

// МАРШРУТЫ ТОЛЬКО ДЛЯ АВТОРИЗОВАННЫХ (разрешено даже забаненным)
Route::middleware('auth')->group(function () {
    // Профиль
    Route::get('/user', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/user', [ProfileController::class, 'update'])->name('profile.update');
    
    // Закладки разрешены даже временно забаненным
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/topics/{topic}/bookmark', [BookmarkController::class, 'toggle'])->name('topics.bookmark');
    
    // ВСЕ действия с уведомлениями разрешены даже забаненным
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/mark-dropdown-viewed', [NotificationController::class, 'markDropdownAsViewed'])->name('notifications.markDropdownViewed');
    
    // ПЕРЕНЕСЕНО: Активные действия с уведомлениями (теперь доступно забаненным)
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/cleanup-ban', function () {
    $count = auth()->user()->notifications()
        ->whereIn('type', ['temporary_ban', 'permanent_ban'])
        ->delete();
        
    return response()->json([
        'success' => true,
        'deleted_count' => $count
    ]);
})->name('notifications.cleanup-ban');

    // Проверка статуса бана
    Route::get('/user/ban-status', function () {
        return response()->json([
            'banned' => auth()->user()->isBanned(),
            'ban_type' => auth()->user()->getBanType()
        ]);
    })->name('user.ban-status');
});

// ЗАЩИЩЁННЫЕ МАРШРУТЫ - требуют авторизации И проверки на бан
Route::middleware(['auth', 'check.banned'])->group(function () {
// Загрузка изображений галереи во временную папку
Route::post('/gallery/upload', [ImageController::class, 'uploadGalleryImage'])
    ->name('gallery.upload')
    ->middleware('throttle:10,1');
// Личные сообщения
// Личные сообщения
Route::middleware(['auth', 'check.banned'])->group(function () {
    Route::get('/messages', [App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/user/{userId}', [App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/user/{userId}', [App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');
    Route::delete('/messages/{messageId}', [App\Http\Controllers\MessageController::class, 'destroy'])->name('messages.destroy');
    Route::get('/messages/conversation/{conversationId}/load-more', [App\Http\Controllers\MessageController::class, 'loadMore'])->name('messages.loadMore');
    Route::get('/messages/conversation/{conversationId}/search', [App\Http\Controllers\MessageController::class, 'search'])->name('messages.search');
});
// Удаление временных изображений галереи  
Route::post('/gallery/delete-image', [ImageController::class, 'deleteGalleryImage'])
    ->name('gallery.delete');
Route::post('/files/upload', [TopicController::class, 'uploadFile'])->name('files.upload');
Route::delete('/files/delete-temp', [TopicController::class, 'deleteTempFile'])->name('files.delete-temp');

    // ВАЖНО: Специфичные маршруты тем ПЕРЕД общим {topic}
    Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    Route::post('/topics/upload-image', [TopicController::class, 'uploadImage'])->name('topics.upload-image');
    // API для получения тегов (AJAX)
Route::get('/api/categories/{category}/tags', [App\Http\Controllers\TagController::class, 'getTagsByCategory'])->name('api.tags.by-category');

    // Редактирование конкретных тем
    Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
    Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update'); // ИСПРАВЛЕНО!
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy'); // ДОБАВЛЕНО!
    
    // УБРАТЬ ЭТИ СТРОКИ ОТСЮДА:
    // Route::post('/topics/{topic}/pin', [App\Http\Controllers\ModerationController::class, 'changePinType'])->name('topic.pin');
    // Route::delete('/posts/{post}', [App\Http\Controllers\ModerationController::class, 'deletePost'])->name('post.delete');
    
    // Создание и редактирование постов
    Route::post('/posts', [PostController::class, 'store'])
    ->name('posts.store')
    ->middleware('throttle:posts'); // Уже есть, хорошо!

    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
    
    // Загрузка изображений
    Route::post('/images/upload', [ImageController::class, 'uploadAndCompress'])
    ->name('images.upload')
    ->middleware('throttle:5,1'); // 5 загрузок в минуту

    
    // Лайки - ОСТАЮТСЯ В ЗАЩИЩЕННОЙ ГРУППЕ (недоступно забаненным)
    Route::post('/topics/{topic}/like', [LikeController::class, 'toggleTopic'])->name('likes.topic');
    Route::post('/posts/{post}/like', [LikeController::class, 'togglePost'])->name('likes.post');
});

// Просмотр тем - ВАЖНО: Ставим в конце, чтобы не перехватывал /topics/create
Route::get('/topics/{topic}', [TopicController::class, 'show'])->name('topics.show')->middleware(['web', 'topic.redirect']);

// Маршруты модерации (только для модераторов и админов)
Route::middleware(['auth', 'role:moderator,admin'])->prefix('moderation')->name('moderation.')->group(function () {
    // Управление категориями и тегами
    Route::get('/categories', [App\Http\Controllers\ModerationController::class, 'categories'])->name('categories');
    Route::post('/categories', [App\Http\Controllers\ModerationController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [App\Http\Controllers\ModerationController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [App\Http\Controllers\ModerationController::class, 'deleteCategory'])->name('categories.delete');
        // ДОБАВИТЬ ЭТУ СТРОКУ ТОЛЬКО ДЛЯ АДМИНОВ:
    Route::get('/users/{user}/activity', [App\Http\Controllers\ModerationController::class, 'getUserActivity'])
        ->name('user.activity')
        ->middleware('role:admin'); // КРИТИЧНО! Только админы!

    Route::post('/tags', [App\Http\Controllers\ModerationController::class, 'storeTag'])->name('tags.store');
    Route::put('/tags/{tag}', [App\Http\Controllers\ModerationController::class, 'updateTag'])->name('tags.update');
    Route::delete('/tags/{tag}', [App\Http\Controllers\ModerationController::class, 'deleteTag'])->name('tags.delete');
    Route::get('/', [App\Http\Controllers\ModerationController::class, 'index'])->name('index');
    Route::get('/users', [App\Http\Controllers\ModerationController::class, 'users'])->name('users');
    Route::post('/users/{user}/ban', [App\Http\Controllers\ModerationController::class, 'banUser'])->name('user.ban');
    Route::post('/users/{user}/unban', [App\Http\Controllers\ModerationController::class, 'unbanUser'])->name('user.unban');
    
    Route::post('/users/{user}/role', [App\Http\Controllers\ModerationController::class, 'changeRole'])
        ->name('user.role')
        ->middleware('role:admin');
        Route::get('/users/{user}/activity', [App\Http\Controllers\ModerationController::class, 'getUserActivity'])
        ->name('user.activity')
        ->middleware('role:admin');
        
    Route::delete('/users/{user}/topics', [App\Http\Controllers\ModerationController::class, 'deleteAllUserTopics'])
        ->name('user.delete-topics')
        ->middleware('role:admin');
        
    Route::delete('/users/{user}/posts', [App\Http\Controllers\ModerationController::class, 'deleteAllUserPosts'])
        ->name('user.delete-posts')
        ->middleware('role:admin');

    Route::delete('/topics/{topic}', [App\Http\Controllers\ModerationController::class, 'deleteTopic'])->name('topic.delete');
    Route::post('/topics/{topic}/move', [App\Http\Controllers\ModerationController::class, 'moveTopic'])->name('topic.move');
    Route::post('/topics/{topic}/toggle-status', [App\Http\Controllers\ModerationController::class, 'toggleTopicStatus'])->name('topic.toggle-status');
    
    // ДОБАВИТЬ ЭТУ СТРОКУ В ГРУППУ МОДЕРАЦИИ:
    Route::post('/topics/{topic}/pin', [App\Http\Controllers\ModerationController::class, 'changePinType'])->name('topic.pin');
    
    Route::delete('/posts/{post}', [App\Http\Controllers\ModerationController::class, 'deletePost'])->name('post.delete');
});