<?php

namespace App\Http\Controllers;

use App\Models\PostFile;
use App\Models\TopicFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class FileController extends Controller
{
    public function download($hashedId)
    {
        try {
            // Расшифровываем ID
            $fileData = Crypt::decrypt($hashedId);
            $fileId = $fileData['id'];
            $fileType = $fileData['type']; // 'post' или 'topic'
        } catch (\Exception $e) {
            abort(404, 'Неверная ссылка на файл');
        }

        // Находим файл в зависимости от типа
        if ($fileType === 'post') {
            $file = PostFile::find($fileId);
        } elseif ($fileType === 'topic') {
            $file = TopicFile::find($fileId);
        } else {
            abort(404, 'Неверный тип файла');
        }

        if (!$file) {
            abort(404, 'Файл не найден');
        }
if ($fileType === 'post') {
    $post = $file->post;
    // Проверяем, может ли пользователь видеть этот пост
    if (!$post || !$post->topic || $post->topic->is_private) {
        // Дополнительные проверки для приватных тем
        if (!auth()->check() || (!auth()->user()->canModerate() && $post->topic->user_id !== auth()->id())) {
            abort(403, 'Нет доступа к файлу');
        }
    }
} elseif ($fileType === 'topic') {
    $topic = $file->topic;
    // Проверяем, может ли пользователь видеть эту тему
    if (!$topic || $topic->is_private) {
        if (!auth()->check() || (!auth()->user()->canModerate() && $topic->user_id !== auth()->id())) {
            abort(403, 'Нет доступа к файлу');
        }
    }
}

// Логируем подозрительные попытки доступа
if (!auth()->check() && env('ALLOW_FILE_DOWNLOAD', 1) == 1) {
    \Log::warning('Unauthorized file access attempt', [
        'file_id' => $fileId,
        'file_type' => $fileType,
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent()
    ]);
}

        // Проверяем настройку загрузки файлов из .env
        $allowFileDownload = env('ALLOW_FILE_DOWNLOAD', 1);
        
        if ($allowFileDownload == 0 && !auth()->check()) {
            // Сохраняем URL для перенаправления после авторизации
            session(['intended_url' => request()->url()]);
            
            return redirect()->route('login')->with('error', 'Для скачивания файлов необходимо зарегистрироваться');
        }

        // Проверяем существование файла на диске
        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'Файл не найден на сервере');
        }

        // Получаем полный путь к файлу
        $filePath = Storage::disk('public')->path($file->file_path);

        // Увеличиваем счетчик скачиваний
        $file->increment('downloads_count');

        // Логируем скачивание
        \Log::info('File downloaded', [
            'file_id' => $fileId,
            'file_type' => $fileType,
            'file_name' => $file->original_name,
            'user_id' => auth()->id() ?? 'guest',
            'ip' => request()->ip(),
            'downloads_count' => $file->downloads_count
        ]);

        // Возвращаем файл для скачивания
        return response()->download($filePath, $file->original_name, [
            'Content-Type' => $file->mime_type ?? 'application/octet-stream',
        ]);
    }

    /**
     * Генерирует безопасную ссылку для скачивания файла
     */
    public static function generateDownloadUrl($fileId, $fileType)
    {
        $data = [
            'id' => $fileId,
            'type' => $fileType,
            'timestamp' => time()
        ];
        
        $hashedId = Crypt::encrypt($data);
        
        return route('file.download', ['hashedId' => $hashedId]);
    }
}