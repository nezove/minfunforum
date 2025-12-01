<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function uploadAndCompress(Request $request)
    {
        // Проверка на бан
        if (!auth()->user()->canPerformActions()) {
            return response()->json([
                'success' => false,
                'message' => 'Ваш аккаунт заблокирован. Вы не можете загружать изображения.'
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);
        // Определяем контекст использования
$isTopicCreation = $request->header('Referer') && 
                   (strpos($request->header('Referer'), '/topics/create') !== false);

// Для создания тем - временные папки, для постов - постоянные
$useTemporary = $isTopicCreation;

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
            // Защита от DoS через размер
if ($originalWidth > 4000 || $originalHeight > 4000) {
    throw new \Exception('Изображение слишком большое. Максимум 4000x4000 пикселей');
}

// Защита от DoS через количество пикселей (потребление памяти)
if ($originalWidth * $originalHeight > 16000000) { // ~16MP max
    throw new \Exception('Изображение требует слишком много памяти для обработки');
}


            if ($originalWidth < 10 || $originalHeight < 10) {
                throw new \Exception('Изображение слишком маленькое');
            }
            
            // Создаем изображение из файла
            $sourceImage = $this->createImageFromFile($image);
            if (!$sourceImage) {
                throw new \Exception('Не удалось создать изображение');
            }
            
            // Пути для временного сохранения
            // Пути в зависимости от контекста
if ($useTemporary) {
    $originalPath = "temp/images/{$randomName}_original.jpg";
    $thumbnailPath = "temp/images/{$randomName}_thumb.jpg";
    $storageDir = storage_path('app/public/temp/images');
} else {
    $originalPath = "images/originals/{$randomName}_original.jpg";
    $thumbnailPath = "images/thumbnails/{$randomName}_thumb.jpg";
    $storageDir = storage_path('app/public/images');
}

            
            // Создаем оригинал
            $originalImage = imagecreatetruecolor($originalWidth, $originalHeight);
            $white = imagecolorallocate($originalImage, 255, 255, 255);
            imagefill($originalImage, 0, 0, $white);
            imagecopyresampled(
                $originalImage, $sourceImage,
                0, 0, 0, 0,
                $originalWidth, $originalHeight,
                $originalWidth, $originalHeight
            );
            
            // Создаем миниатюру (максимум 800px по большей стороне)
            $maxSize = 800;
            if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
                $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
                $thumbWidth = (int)($originalWidth * $ratio);
                $thumbHeight = (int)($originalHeight * $ratio);
            } else {
                $thumbWidth = $originalWidth;
                $thumbHeight = $originalHeight;
            }
            
            $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            $whiteThumb = imagecolorallocate($thumbnailImage, 255, 255, 255);
            imagefill($thumbnailImage, 0, 0, $whiteThumb);
            imagecopyresampled(
                $thumbnailImage, $sourceImage,
                0, 0, 0, 0,
                $thumbWidth, $thumbHeight,
                $originalWidth, $originalHeight
            );
            
            // Создаем директории если не существуют
            if ($useTemporary) {
    if (!file_exists(storage_path('app/public/temp/images'))) {
        mkdir(storage_path('app/public/temp/images'), 0755, true);
    }
} else {
    if (!file_exists(storage_path('app/public/images/originals'))) {
        mkdir(storage_path('app/public/images/originals'), 0755, true);
    }
    if (!file_exists(storage_path('app/public/images/thumbnails'))) {
        mkdir(storage_path('app/public/images/thumbnails'), 0755, true);
    }
}

            
            // Сохраняем изображения во временную папку
            imagejpeg($originalImage, storage_path('app/public/' . $originalPath), 95);
            imagejpeg($thumbnailImage, storage_path('app/public/' . $thumbnailPath), 90);
            
            // Освобождаем память
            imagedestroy($sourceImage);
            imagedestroy($originalImage);
            imagedestroy($thumbnailImage);
            
            // Создаем запись о временном файле
            if ($useTemporary) {
    $tempFile = \App\Models\TemporaryFile::createTempImage([
        'filename' => $randomName . '.jpg',
        'original_name' => $image->getClientOriginalName(),
        'original_path' => $originalPath,
        'thumbnail_path' => $thumbnailPath,
        'file_size' => filesize(storage_path('app/public/' . $originalPath)),
        'width' => $originalWidth,
        'height' => $originalHeight,
        'mime_type' => 'image/jpeg'
    ], 'image');}


           $responseData = [
    'success' => true,
    'imageUrl' => Storage::disk('public')->url($thumbnailPath),
    'originalUrl' => Storage::disk('public')->url($originalPath)
];

if ($useTemporary && isset($tempFile)) {
    $responseData['temp_file_id'] = $tempFile->id;
}

return response()->json($responseData);


        } catch (\Exception $e) {
            \Log::error('Image upload error in editor', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
        // Логируем подозрительную активность
if (strpos($e->getMessage(), 'Недопустимый') !== false || 
    strpos($e->getMessage(), 'Поврежденный') !== false) {
    \Log::warning('Suspicious file upload attempt', [
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'filename' => $image->getClientOriginalName(),
        'size' => $image->getSize(),
        'error' => $e->getMessage()
    ]);
}

    }


    private function generateRandomName($length = 14)
{
    // Используем криптографически стойкий генератор
    return substr(bin2hex(random_bytes($length)), 0, $length);
}


private function createImageFromFile($file)
    {
        $mimeType = $file->getMimeType();
        
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($file->getPathname());
            case 'image/png':
                return imagecreatefrompng($file->getPathname());
            case 'image/gif':
                return imagecreatefromgif($file->getPathname());
            case 'image/webp':
                return imagecreatefromwebp($file->getPathname());
            default:
                return false;
        }
    }

/**
     * Загрузка изображения для галереи (временное сохранение)
     */
    public function uploadGalleryImage(Request $request)
    {
        if (!auth()->user()->canPerformActions()) {
            return response()->json(['success' => false, 'message' => 'Ваш аккаунт заблокирован.'], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        try {
            $image = $request->file('image');
            $randomName = $this->generateRandomName(16);
            
            $imageInfo = getimagesize($image->getPathname());
            if (!$imageInfo) {
                throw new \Exception('Не удалось получить информацию об изображении');
            }
            
            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            
            if ($originalWidth > 4000 || $originalHeight > 4000) {
                throw new \Exception('Изображение слишком большое');
            }

            $sourceImage = $this->createImageFromFile($image);
            if (!$sourceImage) {
                throw new \Exception('Не удалось создать изображение');
            }
            
            // Пути для временного сохранения
            $originalPath = "temp/gallery/{$randomName}.jpg";
            $thumbnailPath = "temp/gallery/{$randomName}_thumb.jpg";
            
            // Создаем оригинал
            $originalImage = imagecreatetruecolor($originalWidth, $originalHeight);
            $white = imagecolorallocate($originalImage, 255, 255, 255);
            imagefill($originalImage, 0, 0, $white);
            imagecopyresampled($originalImage, $sourceImage, 0, 0, 0, 0, $originalWidth, $originalHeight, $originalWidth, $originalHeight);
            
            // Создаем миниатюру
            $maxThumbSize = 300;
            $ratio = min($maxThumbSize / $originalWidth, $maxThumbSize / $originalHeight);
            $thumbWidth = (int)($originalWidth * $ratio);
            $thumbHeight = (int)($originalHeight * $ratio);
            
            $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            $whiteThumb = imagecolorallocate($thumbnailImage, 255, 255, 255);
            imagefill($thumbnailImage, 0, 0, $whiteThumb);
            imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $originalWidth, $originalHeight);
            
            // Создаем папку если не существует
            $tempGalleryDir = storage_path('app/public/temp/gallery');
            if (!file_exists($tempGalleryDir)) {
                mkdir($tempGalleryDir, 0755, true);
            }
            
            // Сохраняем изображения
            imagejpeg($originalImage, storage_path('app/public/' . $originalPath), 95);
            imagejpeg($thumbnailImage, storage_path('app/public/' . $thumbnailPath), 90);
            
            imagedestroy($sourceImage);
            imagedestroy($originalImage);
            imagedestroy($thumbnailImage);
            
            $originalFileSize = filesize(storage_path('app/public/' . $originalPath));
            
            // Создаем запись о временном файле
            $tempFile = \App\Models\TemporaryFile::createTempImage([
                'filename' => $randomName . '.jpg',
                'original_name' => $image->getClientOriginalName(),
                'original_path' => $originalPath,
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $originalFileSize,
                'width' => $originalWidth,
                'height' => $originalHeight,
                'mime_type' => 'image/jpeg'
            ], 'gallery');

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $tempFile->id,
                    'filename' => $randomName . '.jpg',
                    'original_name' => $image->getClientOriginalName(),
                    'file_size' => $originalFileSize,
                    'width' => $originalWidth,
                    'height' => $originalHeight,
                    'thumbnail_url' => Storage::disk('public')->url($thumbnailPath),
                    'original_url' => Storage::disk('public')->url($originalPath),
                    'original_path' => $originalPath,
                    'thumbnail_path' => $thumbnailPath
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Удаление временного изображения галереи
     */
    public function deleteGalleryImage(Request $request)
    {
        $request->validate(['image_id' => 'required|integer']);

        try {
            $tempFile = \App\Models\TemporaryFile::where('id', $request->input('image_id'))
                ->where('user_id', auth()->id())
                ->where('file_type', 'gallery')
                ->where('expires_at', '>', now())
                ->first();

            if (!$tempFile) {
                return response()->json(['success' => false, 'message' => 'Изображение не найдено.'], 404);
            }

            $tempFile->deleteCompletely();
            return response()->json(['success' => true, 'message' => 'Изображение удалено.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка при удалении.'], 500);
        }
    }
private function validateImageHeader($header, $mimeType)
{
    $signatures = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89PNG\r\n\x1A\n"],
        'image/gif' => ["GIF87a", "GIF89a"]
    ];
    
    if (!isset($signatures[$mimeType])) {
        return false;
    }
    
    foreach ($signatures[$mimeType] as $signature) {
        if (strpos($header, $signature) === 0) {
            return true;
        }
    }
    
    return false;
}

}