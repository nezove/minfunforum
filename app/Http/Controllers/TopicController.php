<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\TemporaryFile;
use App\Models\Category;
use App\Models\Tag;
use App\Helpers\SeoHelper;
use App\Models\TopicFile;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\TopicGalleryImage;

class TopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['show']);
    }

public function show($id)
{
    $topic = Topic::with(['user', 'category', 'files'])->findOrFail($id);
$topic->load('galleryImages');

// Вычисляем размер галереи
$galleryTotalSize = $topic->galleryImages ? $topic->galleryImages->sum('file_size') : 0;
$formattedGallerySize = $this->formatBytes($galleryTotalSize);

    if (!auth()->check()) {
        session(['intended_url' => request()->url()]);
    }
    
    $viewKey = "topic_viewed_{$id}_" . (auth()->id() ?? request()->ip());

    if (!session()->has($viewKey)) {
        $topic->incrementViews();
        session()->put($viewKey, true);
    }

    // Отмечаем тему как прочитанную для авторизованного пользователя
    if (auth()->check()) {
        \App\Models\TopicView::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'topic_id' => $topic->id,
            ],
            [
                'viewed_at' => now(),
            ]
        );
    }

    $posts = $topic->posts()
        ->with([
            'user', 
            'parent.user', 
            'replyToPost.user',
            'files'
        ])
        ->oldest()
        ->paginate(20);

    // SEO данные
    $siteName = config('app.name', 'Forum');
    $seoTitle = SeoHelper::generateTopicTitle(
        $topic->title,
        $topic->category->name,
        $siteName
    );
    $seoDescription = SeoHelper::generateTopicDescription(
        $topic->content,
        $topic->user->name,
        $topic->replies_count ?? 0,
        $topic->category->name
    );
    $seoKeywords = SeoHelper::generateKeywords([
        $topic->category->name,
        $topic->user->name,
        'обсуждение',
        'форум',
        'ответы'
    ]);

return view('topics.show', compact(
    'topic', 
    'posts', 
    'seoTitle', 
    'seoDescription', 
    'seoKeywords',
    'formattedGallerySize'
));

}

    public function create(Request $request)
{
    // КРИТИЧЕСКИ ВАЖНО: Проверка на бан
    if (!auth()->user()->canPerformActions()) {
        return redirect()->route('forum.index')->with('error', 'Ваш аккаунт заблокирован. Вы не можете создавать темы.');
    }

    // Загружаем категории с активными тегами
    $categories = Category::with(['activeTags' => function($query) {
        $query->where('is_active', true)->orderBy('name');
    }])->get();
    
    $selectedCategoryId = $request->get('category');
    
    return view('topics.create', compact('categories', 'selectedCategoryId'));
}


    public function store(Request $request)
{
    // КРИТИЧЕСКИ ВАЖНО: Проверка на бан
    if (!auth()->user()->canPerformActions()) {
        return redirect()->route('forum.index')->with('error', 'Ваш аккаунт заблокирован. Вы не можете создавать темы.');
    }

    // НОВОЕ: Дополнительная проверка в базе данных
    $lastTopic = Topic::where('user_id', auth()->id())
        ->where('created_at', '>=', now()->subMinutes(5))
        ->first();
    
    if ($lastTopic) {
        $timeLeft = 5 - $lastTopic->created_at->diffInMinutes(now());
        return back()->withErrors([
            'flood' => "Вы можете создавать не более 1 темы в 5 минут. Осталось ждать: {$timeLeft} мин."
        ])->withInput();
    }

    $request->validate([
        'title' => 'required|max:255',
        'content' => 'required',
        'category_id' => 'required|exists:categories,id',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
        // Валидация галереи
// Валидация данных предзагруженной галереи
'gallery_images' => 'nullable|array|max:20',
'gallery_images.*.filename' => 'required|string',
'gallery_images.*.original_name' => 'required|string',
'gallery_images.*.file_size' => 'required|integer',
'gallery_images.*.width' => 'required|integer',
'gallery_images.*.height' => 'required|integer',
'gallery_images.*.description' => 'nullable|string|max:500',
'gallery_images.*.original_path' => 'required|string',
'gallery_images.*.thumbnail_path' => 'required|string',
    ]);

    $sanitizer = new \App\Services\ContentSanitizer();

    $topic = Topic::create([
        'title' => $request->title,
        'content' => $sanitizer->sanitize($request->content),
        'user_id' => auth()->id(),
        'category_id' => $request->category_id,
        'last_activity_at' => now(),
    ]);

    if ($request->has('tags') && is_array($request->tags)) {
        $topic->syncTags($request->tags);
    }

    // Handle file uploads - работаем с временными файлами
// Обработка всех временных файлов
$this->processTemporaryFiles($topic);
$this->processTemporaryEditorImages($topic);
$this->processGalleryImages($request, $topic);

// Поиск упоминаний в теме
$this->createTopicMentionNotifications($request->content, $topic, auth()->id());
    auth()->user()->increment('topics_count');

    return redirect()->route('topics.show', $topic)->with('success', 'Тема создана успешно!');
}

    public function edit(Topic $topic)
    {
        // Проверяем права на редактирование
    if (!$topic->canEdit()) {
        abort(403, 'У вас нет прав для редактирования этой темы или время редактирования истекло.');
    }

        // Проверка на бан для обычных пользователей
        if ($topic->user_id === auth()->id() && !auth()->user()->canPerformActions()) {
            return back()->with('error', 'Ваш аккаунт заблокирован.');
        }

    $categories = Category::with('activeTags')->get();
    return view('topics.edit', compact('topic', 'categories'));
    }

    public function update(Request $request, Topic $topic)
{
    // Проверяем права на редактирование
    if (!$topic->canEdit()) {
        abort(403, 'У вас нет прав для редактирования этой темы или время редактирования истекло.');
    }

    // Проверка на бан для обычных пользователей
    if ($topic->user_id === auth()->id() && !auth()->user()->canPerformActions()) {
        return back()->with('error', 'Ваш аккаунт заблокирован.');
    }

    $request->validate([
        'title' => 'required|max:255',
        'content' => 'required',
        'category_id' => 'required|exists:categories,id',
        'remove_files' => 'array',
        'remove_files.*' => 'integer|exists:topic_files,id',
        'remove_gallery' => 'array',
        'remove_gallery.*' => 'integer|exists:topic_gallery_images,id',
    ]);

    // НОВОЕ: Конвертируем markdown в HTML перед сохранением
    $content = $request->content; // Quill уже отдает готовый HTML

    // Удаление файлов если указаны
    if ($request->has('remove_files')) {
        foreach ($request->remove_files as $fileId) {
            $file = \App\Models\TopicFile::find($fileId);
            if ($file && $file->topic_id === $topic->id) {
                // Удаляем физический файл
                \Storage::disk('public')->delete($file->file_path);
                // Удаляем запись из БД
                $file->delete();
            }
        }
    }

    // Удаление изображений галереи если указаны
    if ($request->has('remove_gallery')) {
        foreach ($request->remove_gallery as $imageId) {
            $image = \App\Models\TopicGalleryImage::find($imageId);
            if ($image && $image->topic_id === $topic->id) {
                // Удаляем физические файлы
                \Storage::disk('public')->delete($image->image_path);
                \Storage::disk('public')->delete($image->thumbnail_path);
                // Удаляем запись из БД
                $image->delete();
            }
        }
    }

    // НОВОЕ: Отмечаем как отредактированное если контент изменился
    $contentChanged = $topic->title !== $request->title || $topic->content !== $content || $topic->category_id != $request->category_id;

    $topic->update([
        'title' => $request->title,
        'content' => $content, // Сохраняем HTML версию
        'category_id' => $request->category_id,
    ]);

    if ($contentChanged) {
        $topic->markAsEdited();
    }

    return redirect()->route('topics.show', $topic)->with('success', 'Тема обновлена!');
}


    public function destroy(Topic $topic)
{
    // ОБНОВЛЕНО: Используем новый метод проверки
    if (!$topic->canDelete()) {
        abort(403, 'У вас нет прав для удаления этой темы или время удаления истекло.');
    }

    // Модератор не может удалить тему админа (если сам не админ)
    if (!auth()->user()->isAdmin() && $topic->user->isAdmin()) {
        abort(403, 'Модератор не может удалить тему администратора.');
    }

    $topicTitle = $topic->title;
    $userId = $topic->user_id;
    
    // Удаляем файлы (теперь это происходит в событии модели)
    $topic->delete();

    // Уменьшаем счетчик тем пользователя ПОСЛЕ удаления
    $user = \App\Models\User::find($userId);
    if ($user) {
        $user->decrement('topics_count');
    }

    return redirect()->route('forum.index')->with('success', "Тема \"{$topicTitle}\" удалена.");
}


    public function uploadImage(Request $request)
{
    // Проверка авторизации и бана
    if (!auth()->check()) {
        return response()->json([
            'success' => false,
            'message' => 'Необходимо войти в систему для загрузки изображений.'
        ], 401);
    }

    if (!auth()->user()->canPerformActions()) {
        return response()->json([
            'success' => false,
            'message' => 'Ваш аккаунт заблокирован. Вы не можете загружать изображения.'
        ], 403);
    }

    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
    ]);

    try {
        $image = $request->file('image');
        
        // Генерируем случайное имя из 14 символов
        $randomName = $this->generateRandomName(14);

        
        // Получаем информацию об изображении
        $imageInfo = getimagesize($image->getPathname());
        if (!$imageInfo) {
            throw new \Exception('Не удалось получить информацию об изображении');
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Проверка размеров изображения
        if ($originalWidth > 4000 || $originalHeight > 4000) {
            throw new \Exception('Изображение слишком большое. Максимум 4000x4000 пикселей');
        }

        if ($originalWidth < 10 || $originalHeight < 10) {
            throw new \Exception('Изображение слишком маленькое');
        }
        
        // Создаем изображение из файла в зависимости от типа
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($image->getPathname());
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($image->getPathname());
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($image->getPathname());
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($image->getPathname());
                break;
            default:
                throw new \Exception('Неподдерживаемый тип изображения: ' . $mimeType);
        }
        
        if (!$sourceImage) {
            throw new \Exception('Не удалось создать изображение из файла');
        }
        
        // Рассчитываем размеры для превью (максимум 600x400)
        $maxWidth = 600;
        $maxHeight = 400;
        
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $thumbnailWidth = (int)($originalWidth * $ratio);
        $thumbnailHeight = (int)($originalHeight * $ratio);
        
        // Создаем превью
        $thumbnailImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        
        // Устанавливаем белый фон для прозрачных изображений
        $white = imagecolorallocate($thumbnailImage, 255, 255, 255);
        imagefill($thumbnailImage, 0, 0, $white);
        
        imagecopyresampled(
            $thumbnailImage, $sourceImage,
            0, 0, 0, 0,
            $thumbnailWidth, $thumbnailHeight,
            $originalWidth, $originalHeight
        );
        
        // Создаем оригинал (копия для конвертации в JPG)
        $originalImage = imagecreatetruecolor($originalWidth, $originalHeight);
        
        // Устанавливаем белый фон для оригинала
        $whiteOrig = imagecolorallocate($originalImage, 255, 255, 255);
        imagefill($originalImage, 0, 0, $whiteOrig);
        
        imagecopyresampled(
            $originalImage, $sourceImage,
            0, 0, 0, 0,
            $originalWidth, $originalHeight,
            $originalWidth, $originalHeight
        );
        
        // Убеждаемся, что директории существуют
        $thumbnailDir = storage_path('app/public/images/thumbnails');
        $originalDir = storage_path('app/public/images/originals');
        
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }
        if (!file_exists($originalDir)) {
            mkdir($originalDir, 0755, true);
        }
        
        // Сохраняем превью
        $thumbnailPath = $thumbnailDir . '/' . $randomName . '_thumb.jpg';
        $thumbnailSaved = imagejpeg($thumbnailImage, $thumbnailPath, 85);
        
        // Сохраняем оригинал
        $originalPath = $originalDir . '/' . $randomName . '_original.jpg';
        $originalSaved = imagejpeg($originalImage, $originalPath, 95);
        
        // Освобождаем память
        imagedestroy($sourceImage);
        imagedestroy($thumbnailImage);
        imagedestroy($originalImage);
        
        if (!$thumbnailSaved || !$originalSaved) {
            throw new \Exception('Не удалось сохранить изображения');
        }
        
        // Логируем загрузку изображения
        \Log::info('Image uploaded successfully', [
            'user_id' => auth()->id(),
            'filename' => $randomName,
            'original_size' => filesize($originalPath),
            'thumbnail_size' => filesize($thumbnailPath),
            'original_dimensions' => $originalWidth . 'x' . $originalHeight,
            'thumbnail_dimensions' => $thumbnailWidth . 'x' . $thumbnailHeight
        ]);
        
        return response()->json([
            'success' => true,
            'url' => asset('storage/images/originals/' . $randomName . '_original.jpg'),
            'thumbnail_url' => asset('storage/images/thumbnails/' . $randomName . '_thumb.jpg'),
            'original_url' => asset('storage/images/originals/' . $randomName . '_original.jpg'),
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Image upload error', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Ошибка при обработке изображения: ' . $e->getMessage()
        ], 500);
    }
}


    private function createTopicMentionNotifications($content, $topic, $authorId)
    {
        // Поиск упоминаний пользователей (@username)
        preg_match_all('/@([a-zA-Z0-9_а-яёА-ЯЁ]+)/u', $content, $matches);
        
        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);
            
            foreach ($usernames as $username) {
                // Ищем по имени пользователя (не username, а name)
                $user = \App\Models\User::where('username', 'LIKE', $username)->first();
                
                if ($user && $user->id !== $authorId) {
                    Notification::createNotification(
                        $user->id,
                        $authorId,
                        'mention_topic',
                        [
                            'topic_id' => $topic->id,
                            'topic_title' => $topic->title,
                        ]
                    );
                }
            }
        }
    }

    private function generateRandomName($length = 14)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $name = '';
        
        for ($i = 0; $i < $length; $i++) {
            $name .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $name;
    }
    private function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 1) . ' ' . $units[$pow];
}
/**
     * Создает ресурс изображения из файла
     */
    private function createImageFromFile($file)
{
    // Проверяем реальный MIME-тип файла
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file->getPathname());
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($realMimeType, $allowedMimes)) {
        \Log::warning('Invalid MIME type attempted', ['mime' => $realMimeType, 'user_id' => auth()->id()]);
        throw new \Exception('Недопустимый тип файла');
    }
    
    // Дополнительная проверка magic bytes
    $handle = fopen($file->getPathname(), 'rb');
    $header = fread($handle, 12);
    fclose($handle);
    
    if (!$this->validateImageHeader($header, $realMimeType)) {
        throw new \Exception('Поврежденный или поддельный файл');
    }
    
    switch ($realMimeType) {
        case 'image/jpeg':
            return imagecreatefromjpeg($file->getPathname());
        case 'image/png':
            return imagecreatefrompng($file->getPathname());
        case 'image/gif':
            return imagecreatefromgif($file->getPathname());
        default:
            return false;
    }
}
    // Добавьте этот метод в ваш TopicController
public function uploadFile(Request $request)
{
    // Проверка авторизации
    if (!auth()->check()) {
        return response()->json([
            'success' => false,
            'message' => 'Необходимо войти в систему для загрузки файлов.'
        ], 401);
    }

    // Проверка на бан
    if (!auth()->user()->canPerformActions()) {
        return response()->json([
            'success' => false,
            'message' => 'Ваш аккаунт заблокирован. Вы не можете загружать файлы.'
        ], 403);
    }

    $request->validate([
        'file' => 'required|file|max:10240', // 10MB max
    ]);

    try {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Проверяем разрешенные форматы
        $allowedExtensions = ['zip', 'rar', '7z', 'txt', 'pdf', 'doc', 'docx', 'json', 'xml'];
        // Проверяем реальный MIME-тип
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMimeType = finfo_file($finfo, $file->getPathname());
finfo_close($finfo);

$allowedMimeTypes = [
    'application/zip', 'application/x-zip-compressed',
    'application/x-rar-compressed', 'application/vnd.rar',
    'application/x-7z-compressed',
    'text/plain', 'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/json', 'application/xml', 'text/xml'
];

if (!in_array($realMimeType, $allowedMimeTypes)) {
    \Log::warning('Invalid MIME type in file upload', [
        'mime' => $realMimeType, 
        'filename' => $originalName,
        'user_id' => auth()->id()
    ]);
    return response()->json([
        'success' => false,
        'message' => 'Недопустимый тип файла.'
    ], 400);
}

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return response()->json([
                'success' => false,
                'message' => 'Неподдерживаемый формат файла.'
            ], 400);
        }

        // Генерируем уникальное имя файла
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = 'temp/files/' . $filename;

        // Сохраняем файл во временную папку
        $path = $file->storeAs('temp/files', $filename, 'public');

        // Получаем ID текущей сессии
        $sessionId = session()->getId();

        // Создаем запись о временном файле
        $tempFile = TemporaryFile::create([
    'user_id' => auth()->id(),
    'session_id' => session()->getId(),
    'filename' => $filename,
    'original_name' => $originalName,
    'file_path' => $path,
    'file_size' => $fileSize,
    'mime_type' => $mimeType,
    'file_type' => 'file', // используем file_type = 'file'
    'expires_at' => now()->addHours(24)
]);


        // Форматируем размер файла для отображения
        $formattedSize = $this->formatBytes($fileSize);

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $tempFile->id,
                'filename' => $filename,
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'formatted_size' => $formattedSize,
                'mime_type' => $mimeType
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('File upload error', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при загрузке файла: ' . $e->getMessage()
        ], 500);
    }
}


// Также добавьте метод для удаления временных файлов
public function deleteTempFile(Request $request)
{
    if (!auth()->check()) {
        return response()->json([
            'success' => false,
            'message' => 'Необходимо войти в систему.'
        ], 401);
    }

    $request->validate([
        'file_id' => 'required|integer'
    ]);

    try {
        $fileId = $request->input('file_id');

        // УБИРАЕМ проверку session_id и file_type - они слишком строгие
        $tempFile = TemporaryFile::where('id', $fileId)
            ->where('user_id', auth()->id())  // Только проверка владельца
            ->first();  // Убрали where('expires_at', '>', now()) на случай если файл просрочен

        if (!$tempFile) {
            // Логируем для отладки
            \Log::warning('Temp file not found for deletion', [
                'file_id' => $fileId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Файл не найден или у вас нет прав на его удаление.'
            ], 404);
        }

        // Логируем успешное удаление
        \Log::info('Deleting temp file', [
            'file_id' => $tempFile->id,
            'file_type' => $tempFile->file_type,
            'file_path' => $tempFile->file_path,
            'user_id' => $tempFile->user_id,
        ]);

        // Удаляем физический файл с диска
        if (Storage::disk('public')->exists($tempFile->file_path)) {
            Storage::disk('public')->delete($tempFile->file_path);
        }

        // Удаляем запись из базы
        $tempFile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Файл удален.'
        ]);

    } catch (\Exception $e) {
        \Log::error('File delete error', [
            'user_id' => auth()->id(),
            'file_id' => $request->input('file_id'),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при удалении файла: ' . $e->getMessage()
        ], 500);
    }
}

public function markAllAsRead()
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо войти в систему.'
            ], 401);
        }

        try {
            // Получаем все темы
            $topics = Topic::all();

            // Для каждой темы создаем или обновляем запись о просмотре
            foreach ($topics as $topic) {
                \App\Models\TopicView::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'topic_id' => $topic->id
                    ],
                    [
                        'viewed_at' => now()
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Все темы отмечены как прочитанные.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Mark all as read error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке запроса.'
            ], 500);
        }
    }

private function processTemporaryFiles($topic)
    {
        $tempFiles = \App\Models\TemporaryFile::where('file_type', 'file')
            ->where('expires_at', '>', now())
            ->where('user_id', auth()->id())
            ->get();

        foreach ($tempFiles as $tempFile) {
            if (!Storage::disk('public')->exists($tempFile->file_path)) continue;

            $allowedExtensions = ['zip', 'rar', '7z', 'txt', 'pdf', 'doc', 'docx', 'json', 'xml'];
$extension = strtolower(pathinfo($tempFile->original_name, PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    \Log::warning('Invalid file extension attempted', ['file' => $tempFile->original_name, 'user_id' => auth()->id()]);
    continue;
}
$permanentFilename = Str::uuid() . '.' . $extension;
            $permanentPath = 'files/' . $permanentFilename;
            
            if (Storage::disk('public')->copy($tempFile->file_path, $permanentPath)) {
                TopicFile::create([
                    'topic_id' => $topic->id,
                    'filename' => $permanentFilename,
                    'original_name' => $tempFile->original_name,
                    'file_path' => $permanentPath,
                    'file_size' => $tempFile->file_size,
                    'mime_type' => $tempFile->mime_type,
                ]);
                $tempFile->deleteCompletely();
            }
        }
    }

    private function processTemporaryEditorImages($topic)
    {
        $tempImages = \App\Models\TemporaryFile::where('file_type', 'image')
            ->where('expires_at', '>', now())
            ->where('user_id', auth()->id())
            ->get();

        foreach ($tempImages as $tempImage) {
            if (!Storage::disk('public')->exists($tempImage->file_path) || 
                !Storage::disk('public')->exists($tempImage->thumbnail_path)) continue;

            $randomName = $this->generateRandomName(14);

            $permanentOriginalPath = "images/originals/{$randomName}_original.jpg";
            $permanentThumbnailPath = "images/thumbnails/{$randomName}_thumb.jpg";

            $this->ensureDirectoryExists(storage_path('app/public/images/originals'));
            $this->ensureDirectoryExists(storage_path('app/public/images/thumbnails'));

            if (Storage::disk('public')->copy($tempImage->file_path, $permanentOriginalPath) &&
                Storage::disk('public')->copy($tempImage->thumbnail_path, $permanentThumbnailPath)) {
                
                $oldOriginalUrl = Storage::disk('public')->url($tempImage->file_path);
                $oldThumbnailUrl = Storage::disk('public')->url($tempImage->thumbnail_path);
                $newOriginalUrl = Storage::disk('public')->url($permanentOriginalPath);
                $newThumbnailUrl = Storage::disk('public')->url($permanentThumbnailPath);

                $topic->content = str_replace($oldOriginalUrl, $newOriginalUrl, $topic->content);
                $topic->content = str_replace($oldThumbnailUrl, $newThumbnailUrl, $topic->content);
                $topic->save();

                $tempImage->deleteCompletely();
            }
        }
    }

    private function processGalleryImages($request, $topic)
    {
        if (!$request->has('gallery_images')) return;

        $category = Category::find($request->category_id);
        if (!$category || !$category->allow_gallery) return;

        foreach ($request->input('gallery_images') as $index => $imageData) {
            if (!isset($imageData['original_path']) || !isset($imageData['thumbnail_path'])) continue;
            if (!Storage::disk('public')->exists($imageData['original_path']) ||
                !Storage::disk('public')->exists($imageData['thumbnail_path'])) continue;

            $randomName = $this->generateRandomName(16);
            $permanentOriginalPath = "gallery/originals/{$randomName}.jpg";
            $permanentThumbnailPath = "gallery/thumbnails/{$randomName}.jpg";

            $this->ensureDirectoryExists(storage_path('app/public/gallery/originals'));
            $this->ensureDirectoryExists(storage_path('app/public/gallery/thumbnails'));

            if (Storage::disk('public')->copy($imageData['original_path'], $permanentOriginalPath) &&
                Storage::disk('public')->copy($imageData['thumbnail_path'], $permanentThumbnailPath)) {
                
                TopicGalleryImage::create([
                    'topic_id' => $topic->id,
                    'image_path' => $permanentOriginalPath,
                    'thumbnail_path' => $permanentThumbnailPath,
                    'original_name' => $imageData['original_name'],
                    'description' => $imageData['description'] ?? null,
                    'file_size' => $imageData['file_size'],
                    'width' => $imageData['width'],
                    'height' => $imageData['height'],
                    'sort_order' => $index + 1
                ]);

                Storage::disk('public')->delete($imageData['original_path']);
                Storage::disk('public')->delete($imageData['thumbnail_path']);

                \App\Models\TemporaryFile::where('file_path', $imageData['original_path'])
                    ->where('file_type', 'gallery')
                    ->where('user_id', auth()->id())
                    ->delete();
            }
        }
    }

    private function ensureDirectoryExists($path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function generateRandomImageName($length = 14)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $name = '';
        for ($i = 0; $i < $length; $i++) {
            $name .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $name;
    }
}