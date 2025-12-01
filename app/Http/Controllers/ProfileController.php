<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Topic;
use App\Helpers\SeoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // ← ДОБАВИТЬ ЭТУ СТРОКУ!
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['show']);
    }

    public function show($id)
    {
        $user = User::with([
            'topics' => function($query) {
                $query->latest()->limit(10);
            }, 
            'posts' => function($query) {
                $query->with(['topic.category'])->latest()->limit(10);
            }
        ])->findOrFail($id);

        // Загружаем понравившиеся посты
        $likedPosts = $user->belongsToMany(Post::class, 'likes', 'user_id', 'likeable_id')
            ->where('likes.likeable_type', Post::class)
            ->with(['user', 'topic.category'])
            ->withTimestamps()
            ->orderBy('likes.created_at', 'desc')
            ->limit(10)
            ->get();

        // Загружаем понравившиеся темы
        $likedTopics = $user->belongsToMany(Topic::class, 'likes', 'user_id', 'likeable_id')
            ->where('likes.likeable_type', Topic::class)
            ->with(['user', 'category'])
            ->withTimestamps()
            ->orderBy('likes.created_at', 'desc')
            ->limit(5)
            ->get();

        // SEO данные
        $siteName = config('app.name', 'Forum');
        $seoTitle = SeoHelper::generateProfileTitle($user->username, $siteName);
        $seoDescription = SeoHelper::generateProfileDescription(
            $user->username,
            $user->topics->count(),
            $user->posts->count(),
            $user->role ?? 'user',
            $user->bio
        );
        $seoKeywords = SeoHelper::generateKeywords([
            $user->username,
            'профиль',
            'участник форума',
            $user->role ?? 'user'
        ]);

        return view('profile.show', compact(
            'user', 
            'likedPosts',
            'likedTopics',
            'seoTitle', 
            'seoDescription', 
            'seoKeywords'
        ));
    }
    
    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        // Логирование для отладки
        \Log::info('Profile update request', [
            'user_id' => auth()->id(),
            'is_ajax' => $request->ajax(),
            'has_avatar' => $request->hasFile('avatar'),
            'method' => $request->method(),
            'updated_fields' => array_keys($request->only(['name', 'bio', 'location', 'website'])),
            'ip' => $request->ip()
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-\.\']+$/u'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255', 'regex:/^[\pL\pN\s\-\.\,\']+$/u'],
            'website' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\/.+/'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // 5MB max
        ];

        // Валидация смены пароля только если указан текущий пароль
        if ($request->filled('current_password')) {
            $rules['current_password'] = ['required', 'string'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        try {
            $validatedData = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Проверяем текущий пароль если пытаемся его сменить
        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Неверный текущий пароль'
                    ], 422);
                }
                return back()->withErrors(['current_password' => 'Неверный текущий пароль.']);
            }
            $validatedData['password'] = Hash::make($request->password);
        }

        // ДОБАВИТЬ: Санитизацию текстовых полей
        $sanitizer = new \App\Services\ContentSanitizer();

        if (isset($validatedData['bio'])) {
            $validatedData['bio'] = $sanitizer->sanitize($validatedData['bio']);
        }

        if (isset($validatedData['name'])) {
            $validatedData['name'] = strip_tags($validatedData['name']);
        }

        if (isset($validatedData['location'])) {
            $validatedData['location'] = strip_tags($validatedData['location']);
        }

        // Обработка загрузки аватара
        if ($request->hasFile('avatar')) {
            try {
                $avatarFile = $request->file('avatar');
                
                // БЕЗОПАСНОСТЬ: Проверка MIME-типа и реального содержимого
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileMime = $avatarFile->getMimeType();
                
                if (!in_array($fileMime, $allowedMimes)) {
                    throw new \Exception('Недопустимый тип файла. Разрешены только изображения.');
                }
                
                // КРИТИЧНО: Проверка реального содержимого файла
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMimeType = finfo_file($finfo, $avatarFile->getPathname());
                finfo_close($finfo);
                
                if (!in_array($realMimeType, $allowedMimes)) {
                    throw new \Exception('Файл не является действительным изображением.');
                }
                
                // БЕЗОПАСНОСТЬ: Проверка размера изображения
                $imageInfo = getimagesize($avatarFile->getPathname());
                if (!$imageInfo) {
                    throw new \Exception('Не удалось обработать изображение.');
                }
                
                // Ограничиваем размеры
                if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
                    throw new \Exception('Изображение слишком большое. Максимум 2000x2000 пикселей.');
                }
                
                \Log::info('Avatar upload validation passed', [
                    'original_name' => $avatarFile->getClientOriginalName(),
                    'declared_mime' => $fileMime,
                    'real_mime' => $realMimeType,
                    'size' => $avatarFile->getSize(),
                    'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1]
                ]);

                // Удаляем старый аватар
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // БЕЗОПАСНОСТЬ: Генерируем случайное имя файла
                $extension = strtolower($avatarFile->getClientOriginalExtension());
                $filename = 'avatar_' . time() . '_' . Str::random(10) . '.' . $extension;
                
                // Сохраняем в безопасную директорию
                $avatarPath = $avatarFile->storeAs('avatars', $filename, 'public');
                $validatedData['avatar'] = $avatarPath;
                
                \Log::info('Avatar uploaded successfully', ['path' => $avatarPath]);
                
            } catch (\Exception $e) {
                \Log::error('Avatar upload error', [
                    'message' => $e->getMessage(),
                    'file_info' => $request->hasFile('avatar') ? [
                        'name' => $avatarFile->getClientOriginalName(),
                        'mime' => $avatarFile->getMimeType(),
                        'size' => $avatarFile->getSize()
                    ] : 'no file'
                ]);
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ошибка загрузки аватара: ' . $e->getMessage()
                    ], 422);
                }
                return back()->withErrors(['avatar' => 'Ошибка загрузки аватара: ' . $e->getMessage()]);
            }
        }

        // Удаляем пароль из данных если он не был изменен
        if (!$request->filled('current_password')) {
            unset($validatedData['password']);
        }

        try {
            $user->update($validatedData);
            
            \Log::info('Profile updated successfully', ['user_id' => $user->id]);
            
            // AJAX ответ
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Профиль успешно обновлен!',
                    'avatar_url' => $user->avatar_url ?? null
                ]);
            }
            
            return back()->with('success', 'Профиль успешно обновлен!');
            
        } catch (\Exception $e) {
            \Log::error('Profile update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обновлении профиля: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['general' => 'Ошибка при обновлении профиля: ' . $e->getMessage()]);
        }
    }
}