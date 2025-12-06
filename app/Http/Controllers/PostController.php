<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Models\PostFile;
use App\Models\Notification;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Применяем rate limiting для создания постов
        $this->middleware('throttle:posts')->only(['store']);
    }

    public function store(Request $request)
    {
        // КРИТИЧЕСКИ ВАЖНО: Проверка на бан
        if (!auth()->user()->canPerformActions()) {
    if ($request->ajax()) {
        return response()->json([
            'success' => false,
            'message' => 'Ваш аккаунт заблокирован.'
        ], 403);
    }
    return redirect()->route('forum.index')->with('error', 'Ваш аккаунт заблокирован.');
}

        $request->validate([
            'content' => 'required',
            'topic_id' => 'required|exists:topics,id',
            'parent_id' => 'nullable|exists:posts,id',
            'reply_to_post_id' => 'nullable|exists:posts,id',
'files.*' => 'nullable|file|max:10240|mimes:zip,rar,txt,pdf,doc,docx,json,xml',

        ]);

        $topic = Topic::findOrFail($request->topic_id);
        
        // КРИТИЧЕСКИ ВАЖНО: Проверка закрытой темы
        if (!$topic->canReply(auth()->user())) {
    if ($request->ajax()) {
        return response()->json([
            'success' => false,
            'message' => 'Тема закрыта для ответов. Только модераторы могут отвечать в закрытых темах.'
        ], 403);
    }
    return back()->with('error', 'Тема закрыта для ответов.');
}

        $userId = auth()->id();
        $topicId = $request->topic_id;

        // Защита 1: Проверка дублирования по хешу контента
        $contentHash = hash('sha256', trim($request->content));
        $duplicateKey = "post_hash_{$userId}_{$contentHash}";
        
        if (Cache::has($duplicateKey)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы уже отправили такое сообщение. Подождите перед отправкой следующего.'
                ], 422);
            }
            return back()->withErrors(['content' => 'Вы уже отправили такое сообщение. Подождите перед отправкой следующего.'])
                        ->withInput();
        }

        // Защита 2: Блокировка повторных отправок (не более 1 поста в 10 секунд)
        $postLockKey = "user_post_lock_{$userId}";
        if (Cache::has($postLockKey)) {
            $timeLeft = Cache::get($postLockKey) - time();
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Подождите {$timeLeft} секунд перед отправкой следующего сообщения."
                ], 429);
            }
            return back()->withErrors(['content' => "Подождите {$timeLeft} секунд перед отправкой следующего сообщения."])
                        ->withInput();
        }

        // Защита 4: Глобальная блокировка от двойных кликов
        $submitLockKey = "post_submit_{$userId}_{$topicId}";
        if (!Cache::add($submitLockKey, true, 30)) { // 30 секунд блокировка
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сообщение уже отправляется. Подождите.'
                ], 409);
            }
            return back()->withErrors(['content' => 'Сообщение уже отправляется. Подождите.'])
                        ->withInput();
        }

        try {
            // Защита 5: Проверка на одинаковые сообщения в теме за последние 5 минут
            $recentSimilarPost = Post::where('user_id', $userId)
                ->where('topic_id', $topicId)
                ->where('content', $request->content)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recentSimilarPost) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Вы уже отправляли такое сообщение в эту тему.'
                    ], 422);
                }
                return back()->withErrors(['content' => 'Вы уже отправляли такое сообщение в эту тему.'])
                        ->withInput();
            }

            $sanitizer = new \App\Services\ContentSanitizer();

$sanitizer = new \App\Services\ContentSanitizer();
$post = Post::create([
    'content' => $sanitizer->sanitize($request->content),
                'user_id' => $userId,
                'topic_id' => $topicId,
                'parent_id' => $request->parent_id,
                'quoted_content' => $request->quoted_content,
                'reply_to_post_id' => $request->reply_to_post_id,
            ]);
$this->processTemporaryPostImages($post);

            // Обработка файлов
            // ИСПРАВЛЕНО: Обработка файлов
if ($request->hasFile('files')) {
    foreach ($request->file('files') as $file) {
        if ($file->isValid()) {
            // БЕЗОПАСНОСТЬ: Строгая проверка типов файлов
            $allowedExtensions = ['txt', 'pdf', 'doc', 'docx', 'zip', 'rar', 'json', 'xml'];
            $allowedMimeTypes = [
                'text/plain',
                'application/pdf', 
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/x-rar-compressed',
                'application/x-rar',
                'application/json',
                'application/xml',
                'text/xml'
            ];

            $extension = strtolower($file->getClientOriginalExtension());
            $declaredMime = $file->getMimeType();

            // Проверка расширения
            if (!in_array($extension, $allowedExtensions)) {
                \Log::warning('File upload blocked - invalid extension', [
                    'user_id' => auth()->id(),
                    'filename' => $file->getClientOriginalName(),
                    'extension' => $extension
                ]);
                continue;
            }

            // КРИТИЧНО: Проверка реального MIME-типа
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMimeType = finfo_file($finfo, $file->getPathname());
            finfo_close($finfo);

            if (!in_array($realMimeType, $allowedMimeTypes)) {
                \Log::warning('File upload blocked - MIME mismatch', [
                    'user_id' => auth()->id(),
                    'filename' => $file->getClientOriginalName(),
                    'declared_mime' => $declaredMime,
                    'real_mime' => $realMimeType
                ]);
                continue;
            }

            // БЕЗОПАСНОСТЬ: Дополнительные проверки для исполняемых файлов
            $dangerousSignatures = [
                "\x4D\x5A",           // PE/EXE
                "\x7F\x45\x4C\x46",   // ELF
                "#!/",                // Shell script
                "<?php",              // PHP script
                "<script",            // HTML/JS script
            ];

            $fileContent = file_get_contents($file->getPathname(), false, null, 0, 1024);
            foreach ($dangerousSignatures as $signature) {
                if (strpos($fileContent, $signature) !== false) {
                    \Log::warning('File upload blocked - dangerous signature', [
                        'user_id' => auth()->id(),
                        'filename' => $file->getClientOriginalName(),
                        'signature' => bin2hex($signature)
                    ]);
                    continue 2; // Пропустить этот файл
                }
            }

            // БЕЗОПАСНОСТЬ: Генерируем случайное имя файла
            $safeFilename = 'file_' . time() . '_' . Str::random(10) . '.' . $extension;
            $path = $file->storeAs('post_files', $safeFilename, 'public');

            PostFile::create([
                'post_id' => $post->id,
                'filename' => $safeFilename,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $realMimeType, // Используем проверенный MIME-тип
            ]);

            \Log::info('File uploaded successfully', [
                'user_id' => auth()->id(),
                'post_id' => $post->id,
                'filename' => $safeFilename,
                'original' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $realMimeType
            ]);
        }
    }
}

            // Создание уведомлений
            $this->createNotifications($post, $topic);

            // Обновляем счетчики
            $topic->increment('replies_count');
            $topic->touch('last_activity_at');
            auth()->user()->increment('posts_count');

            // Устанавливаем блокировки после успешного создания
            Cache::put($duplicateKey, true, 300); // 5 минут блокировка дублирования
            Cache::put($postLockKey, time() + 10, 10); // 10 секунд блокировка
            // AJAX ответ
            if ($request->ajax()) {
                // Загружаем пост с пользователем и связями для ответа
                $post->load(['user', 'replyToPost.user']);

                // Обработка контента для AJAX ответа

                return response()->json([
                    'success' => true,
                    'message' => 'Ответ опубликован!',
                    'post' => [
                        'id' => $post->id,
                        'content' => $post->content,

                        'user' => [
    'id' => $post->user->id,
    'name' => $post->user->name ?? $post->user->username,
    'username' => $post->user->username,
    'avatar_url' => $post->user->avatar_url,
    'role' => $post->user->role ?? 'user',
    'rating' => $post->user->rating ?? 0,
    'posts_count' => $post->user->posts_count ?? 0,
],
                        'created_at' => $post->created_at->format('d.m.Y H:i'),
                        'likes_count' => 0,
                        'is_liked' => false,
                        'permalink' => $post->permalink,
                        'reply_to_post' => $post->reply_to_post_id && $post->replyToPost ? [
    'id' => $post->replyToPost->id,
    'user' => [
        'name' => $post->replyToPost->user->name ?? $post->replyToPost->user->username,
        'username' => $post->replyToPost->user->username,
    ],
    'content' => \Illuminate\Support\Str::limit(strip_tags($post->replyToPost->content), 200),
    'permalink' => $post->replyToPost->permalink,
] : null,
                        'files' => $post->files->map(function($file) {
                            return [
                                'id' => $file->id,
                                'original_name' => $file->original_name,
                                'download_url' => $file->download_url,
                                'formatted_size' => $file->formatted_size,
                            ];
                        }),
                    ],
                    'topic' => [
                        'replies_count' => $topic->fresh()->replies_count,
                    ]
                ]);
            }

            return redirect($post->permalink)->with('success', 'Ответ опубликован!');

        } catch (\Exception $e) {
            \Log::error('Error creating post: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка при создании сообщения.'
                ], 500);
            }
            
            return back()->withErrors(['content' => 'Произошла ошибка при создании сообщения.'])
                        ->withInput();
        } finally {
            // Убираем блокировку отправки
            Cache::forget($submitLockKey);
        }
    }

public function edit($id)
{
    $post = Post::findOrFail($id);
    
    // ОБНОВЛЕНО: Используем новый метод проверки
    if (!$post->canEdit()) {
        abort(403, 'У вас нет прав для редактирования этого поста или время редактирования истекло.');
    }

    return view('posts.edit', compact('post'));
}

    public function update(Request $request, $id)
{
    $post = Post::findOrFail($id);
    
    // Проверяем права на редактирование
    if (!$post->canEdit()) {
        abort(403, 'У вас нет прав для редактирования этого поста или время редактирования истекло.');
    }

    // Проверка на бан для обычных пользователей
    if ($post->user_id === auth()->id() && !auth()->user()->canPerformActions()) {
        return back()->with('error', 'Ваш аккаунт заблокирован.');
    }

    $request->validate([
        'content' => 'required|min:3|max:10000',
        'remove_files' => 'array',
        'remove_files.*' => 'integer|exists:post_files,id',
    ]);

    // НОВОЕ: Конвертируем markdown в HTML перед сохранением
    $content = $request->content;

    // Удаление файлов если указаны
    if ($request->has('remove_files')) {
        foreach ($request->remove_files as $fileId) {
            $file = \App\Models\PostFile::find($fileId);
            if ($file && $file->post_id === $post->id) {
                // Удаляем физический файл
                \Storage::disk('public')->delete($file->file_path);
                // Удаляем запись из БД
                $file->delete();
            }
        }
    }

    // НОВОЕ: Отмечаем как отредактированное если контент изменился
    $contentChanged = $post->content !== $content;

    $post->update(['content' => $content]); // Сохраняем HTML версию

    if ($contentChanged) {
        $post->markAsEdited();
    }

    return redirect($post->permalink)->with('success', 'Ответ обновлён!');
}



    public function destroy($id)
{
    $post = Post::findOrFail($id);
    
    // ОБНОВЛЕНО: Используем новый метод проверки
    if (!$post->canDelete()) {
        abort(403, 'У вас нет прав для удаления этого поста или время удаления истекло.');
    }

    // Модератор не может удалить пост админа (если сам не админ)
    if (!auth()->user()->isAdmin() && $post->user->isAdmin()) {
        abort(403, 'Модератор не может удалить пост администратора.');
    }

    $topicId = $post->topic_id;
    
    // Удаляем файлы (теперь это происходит в событии модели)
    $post->delete();

    // Обновляем счетчики темы
    $topic = Topic::find($topicId);
    if ($topic) {
        $topic->updateCounters();
    }

    // Уменьшаем счетчик постов пользователя
    $post->user->decrement('posts_count');

    if (request()->ajax()) {
        return response()->json(['success' => true]);
    }

    return back()->with('success', 'Пост удален.');
}


    /**
     * Создание уведомлений для различных случаев
     */
    private function createNotifications(Post $post, Topic $topic)
    {
        $currentUserId = auth()->id();
        $content = $post->content;
        
        // 1. Уведомление автору темы (если это не он сам отвечает)
        if ($topic->user_id !== $currentUserId) {
            Notification::createNotification(
                $topic->user_id,
                $currentUserId,
                'reply',
                [
                    'topic_id' => $topic->id,
                    'topic_title' => $topic->title,
                    'post_id' => $post->id,
                    'post_content' => $content
                ]
            );
        }

        // 2. Уведомление автору поста, на который отвечают
        if ($post->reply_to_post_id) {
            $originalPost = Post::find($post->reply_to_post_id);
            if ($originalPost && $originalPost->user_id !== $currentUserId && $originalPost->user_id !== $topic->user_id) {
                Notification::createNotification(
                    $originalPost->user_id,
                    $currentUserId,
                    'reply_to_post',
                    [
                        'topic_id' => $topic->id,
                        'topic_title' => $topic->title,
                        'post_id' => $post->id,
                        'post_content' => $content,
                        'original_post_id' => $originalPost->id
                    ]
                );
            }
        }

        // 3. Поиск упоминаний пользователей
        preg_match_all('/@([a-zA-Z0-9_а-яёА-ЯЁ]+)/u', $content, $matches);
        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $username) {
                $mentionedUser = User::where('username', 'LIKE', $username)->first();
                
                if ($mentionedUser && $mentionedUser->id !== $currentUserId) {
                    Notification::createNotification(
                        $mentionedUser->id,
                        $currentUserId,
                        'mention',
                        [
                            'topic_id' => $topic->id,
                            'topic_title' => $topic->title,
                            'post_id' => $post->id,
                            'post_content' => $content,
                            'mentioned_in' => 'post'
                        ]
                    );
                }
            }
        }
    }
    /**
 * Обработка временных изображений для поста
 */
private function processTemporaryPostImages($post)
{
    $tempImages = \App\Models\TemporaryFile::where('file_type', 'image')
        ->where('expires_at', '>', now())
        ->where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->limit(10) // Ограничиваем количество
        ->get();

    foreach ($tempImages as $tempImage) {
        // Проверяем, что файлы действительно существуют
        if (!Storage::disk('public')->exists($tempImage->file_path) || 
            !Storage::disk('public')->exists($tempImage->thumbnail_path)) {
            continue;
        }

        // Генерируем новые имена для постоянного хранения
        $randomName = $this->generateRandomName(14);
        $permanentOriginalPath = "images/originals/{$randomName}_original.jpg";
        $permanentThumbnailPath = "images/thumbnails/{$randomName}_thumb.jpg";

        // Создаем директории если не существуют
        $this->ensureDirectoryExists(storage_path('app/public/images/originals'));
        $this->ensureDirectoryExists(storage_path('app/public/images/thumbnails'));

        // Перемещаем файлы в постоянные папки
        if (Storage::disk('public')->copy($tempImage->file_path, $permanentOriginalPath) &&
            Storage::disk('public')->copy($tempImage->thumbnail_path, $permanentThumbnailPath)) {
            
            // Обновляем контент поста, заменяя временные пути на постоянные
            $oldOriginalUrl = Storage::disk('public')->url($tempImage->file_path);
            $oldThumbnailUrl = Storage::disk('public')->url($tempImage->thumbnail_path);
            $newOriginalUrl = Storage::disk('public')->url($permanentOriginalPath);
            $newThumbnailUrl = Storage::disk('public')->url($permanentThumbnailPath);

            // Обновляем контент поста
            $post->content = str_replace($oldOriginalUrl, $newOriginalUrl, $post->content);
            $post->content = str_replace($oldThumbnailUrl, $newThumbnailUrl, $post->content);
            $post->save();

            // Удаляем временные файлы
            $tempImage->deleteCompletely();

            \Log::info('Post image moved from temporary to permanent storage', [
                'post_id' => $post->id,
                'temp_image_id' => $tempImage->id,
                'permanent_original' => $permanentOriginalPath,
                'permanent_thumbnail' => $permanentThumbnailPath,
            ]);
        }
    }
}

/**
 * Создание директории если она не существует
 */
private function ensureDirectoryExists($path)
{
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

/**
 * Генерация случайного имени файла
 */
private function generateRandomName($length = 14)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $name = '';
    for ($i = 0; $i < $length; $i++) {
        $name .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $name;
}

}