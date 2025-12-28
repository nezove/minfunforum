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

        // Загружаем записи стены БЕЗ комментариев (для производительности)
        // Комментарии будут загружаться по запросу через AJAX
        $wallPosts = $user->wallPosts()
            ->withCount('comments')
            ->with([
                'user',
                'likes'
            ])
            ->latest()
            ->paginate(20);

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
            'wallPosts',
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
            'telegram' => ['nullable', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/'],
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

        // Обработка Telegram никнейма
        if ($request->filled('telegram')) {
            $telegram = trim($request->telegram);

            // Извлекаем только никнейм из различных форматов
            $username = null;

            // Проверяем разные форматы URL
            if (preg_match('/(?:https?:\/\/)?(?:www\.)?t\.me\/([a-zA-Z0-9_]{5,32})(?:\/|$)/', $telegram, $matches)) {
                $username = $matches[1];
            } elseif (preg_match('/(?:https?:\/\/)?(?:www\.)?telegram\.(?:org|me)\/([a-zA-Z0-9_]{5,32})(?:\/|$)/', $telegram, $matches)) {
                $username = $matches[1];
            } elseif (preg_match('/^@([a-zA-Z0-9_]{5,32})$/', $telegram, $matches)) {
                // Формат @username
                $username = $matches[1];
            } elseif (preg_match('/^([a-zA-Z0-9_]{5,32})$/', $telegram, $matches)) {
                // Просто username
                $username = $matches[1];
            }

            if ($username) {
                $validatedData['telegram'] = $username;
            } else {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Некорректный Telegram. Формат: username, @username, t.me/username или https://t.me/username'
                    ], 422);
                }
                return back()->withErrors(['telegram' => 'Некорректный Telegram. Используйте username (5-32 символа, только латиница, цифры и _)']);
            }
        } else {
            $validatedData['telegram'] = null;
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

        // Обработка настроек приватности
        $validatedData['allow_wall_posts'] = $request->has('allow_wall_posts') ? true : false;
        $validatedData['allow_messages'] = $request->has('allow_messages') ? true : false;
        $validatedData['allow_search_indexing'] = $request->has('allow_search_indexing') ? true : false;

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

    /**
     * Обновление стиля никнейма
     */
    public function updateUsernameStyle(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'username_style' => 'nullable|string|max:1000',
            'username_style_enabled' => 'boolean',
        ]);

        // Валидация и очистка CSS от опасных конструкций
        $usernameStyle = $validated['username_style'] ?? '';

        if ($usernameStyle) {
            // Запрещаем опасные свойства и конструкции
            $forbidden = [
                'javascript:', 'expression(', 'behavior:', 'vbscript:',
                'onclick', 'onerror', 'onload', 'eval(', 'url(',
                'import', '@import', 'position:', 'top:', 'left:', 'right:', 'bottom:',
                'z-index:', 'opacity:', 'visibility:', 'display:none', 'display: none'
            ];

            foreach ($forbidden as $pattern) {
                if (stripos($usernameStyle, $pattern) !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Недопустимые CSS свойства. Запрещено использовать: позиционирование, JavaScript, внешние ресурсы.'
                    ], 422);
                }
            }

            // Разрешаем только безопасные CSS свойства
            $allowedProperties = [
                'background', 'color', 'text-shadow', 'font-weight', 'font-style',
                'font-family', 'letter-spacing', 'text-decoration', 'text-transform',
                '-webkit-background-clip', '-webkit-text-fill-color',
                'background-clip', 'text-fill-color'
            ];

            // Проверяем что все свойства из разрешенного списка
            preg_match_all('/([a-z-]+)\s*:/i', $usernameStyle, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $property) {
                    $property = trim(strtolower($property));
                    $isAllowed = false;
                    foreach ($allowedProperties as $allowed) {
                        if (strpos($property, $allowed) === 0) {
                            $isAllowed = true;
                            break;
                        }
                    }
                    if (!$isAllowed) {
                        return response()->json([
                            'success' => false,
                            'message' => "Свойство '$property' не разрешено. Разрешены только: " . implode(', ', $allowedProperties)
                        ], 422);
                    }
                }
            }
        }

        // Обновляем стиль
        $user->username_style = $usernameStyle;
        $user->username_style_enabled = $request->input('username_style_enabled', false);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Стиль никнейма успешно обновлен'
        ]);
    }
}