<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emoji;
use App\Models\EmojiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmojiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Список всех смайликов
     */
    public function index()
    {
        $emojis = Emoji::with('category')->orderBy('sort_order')->paginate(50);
        $categories = EmojiCategory::active()->ordered()->get();

        return view('moderation.emojis.index', compact('emojis', 'categories'));
    }

    /**
     * Форма создания смайлика
     */
    public function create()
    {
        $categories = EmojiCategory::active()->ordered()->get();
        return view('moderation.emojis.create', compact('categories'));
    }

    /**
     * Сохранение нового смайлика
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:emoji_categories,id',
            'name' => 'required|string|max:255',
            'keywords' => 'required|string',
            'file' => 'required|file|mimes:png,jpg,jpeg,gif,svg,webp|max:2048',
            'width' => 'nullable|integer|min:16|max:128',
            'height' => 'nullable|integer|min:16|max:128',
        ]);

        // Загрузка файла
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = 'emoji_' . Str::random(16) . '.' . $extension;
            $path = $file->storeAs('emojis', $filename, 'public');

            // Определяем тип файла
            $fileType = in_array($extension, ['gif']) ? 'gif' :
                       (in_array($extension, ['svg']) ? 'svg' : 'image');

            // Получаем размеры изображения если не указаны
            if (!$request->width || !$request->height) {
                if ($fileType !== 'svg') {
                    $imageInfo = getimagesize($file->getRealPath());
                    $validated['width'] = $validated['width'] ?? $imageInfo[0] ?? 24;
                    $validated['height'] = $validated['height'] ?? $imageInfo[1] ?? 24;
                } else {
                    $validated['width'] = $validated['width'] ?? 24;
                    $validated['height'] = $validated['height'] ?? 24;
                }
            }

            $emoji = Emoji::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'file_path' => $path,
                'keywords' => $validated['keywords'],
                'file_type' => $fileType,
                'width' => $validated['width'],
                'height' => $validated['height'],
                'is_active' => $request->has('is_active'),
                'sort_order' => Emoji::max('sort_order') + 1,
            ]);

            return redirect()->route('moderation.emojis.index')
                ->with('success', 'Смайлик успешно добавлен');
        }

        return back()->with('error', 'Не удалось загрузить файл');
    }

    /**
     * Форма редактирования смайлика
     */
    public function edit(Emoji $emoji)
    {
        $categories = EmojiCategory::active()->ordered()->get();
        return view('moderation.emojis.edit', compact('emoji', 'categories'));
    }

    /**
     * Обновление смайлика
     */
    public function update(Request $request, Emoji $emoji)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:emoji_categories,id',
            'name' => 'required|string|max:255',
            'keywords' => 'required|string',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg,webp|max:2048',
            'width' => 'nullable|integer|min:16|max:128',
            'height' => 'nullable|integer|min:16|max:128',
        ]);

        // Если загружен новый файл
        if ($request->hasFile('file')) {
            // Удаляем старый файл
            Storage::disk('public')->delete($emoji->file_path);

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $filename = 'emoji_' . Str::random(16) . '.' . $extension;
            $path = $file->storeAs('emojis', $filename, 'public');

            $fileType = in_array($extension, ['gif']) ? 'gif' :
                       (in_array($extension, ['svg']) ? 'svg' : 'image');

            $emoji->file_path = $path;
            $emoji->file_type = $fileType;

            // Обновляем размеры если не указаны
            if (!$request->width || !$request->height) {
                if ($fileType !== 'svg') {
                    $imageInfo = getimagesize($file->getRealPath());
                    $emoji->width = $imageInfo[0] ?? 24;
                    $emoji->height = $imageInfo[1] ?? 24;
                }
            }
        }

        $emoji->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'keywords' => $validated['keywords'],
            'width' => $validated['width'] ?? $emoji->width,
            'height' => $validated['height'] ?? $emoji->height,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('moderation.emojis.index')
            ->with('success', 'Смайлик успешно обновлен');
    }

    /**
     * Удаление смайлика
     */
    public function destroy(Emoji $emoji)
    {
        Storage::disk('public')->delete($emoji->file_path);
        $emoji->delete();

        return redirect()->route('moderation.emojis.index')
            ->with('success', 'Смайлик успешно удален');
    }

    /**
     * Управление категориями
     */
    public function categories()
    {
        $categories = EmojiCategory::withCount('emojis')->ordered()->get();
        return view('moderation.emojis.categories', compact('categories'));
    }

    /**
     * Создание категории
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
        ]);

        EmojiCategory::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? '😀',
            'is_active' => $request->has('is_active'),
            'sort_order' => EmojiCategory::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Категория успешно создана');
    }

    /**
     * Обновление категории
     */
    public function updateCategory(Request $request, EmojiCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? $category->icon,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Категория успешно обновлена');
    }

    /**
     * Удаление категории
     */
    public function destroyCategory(EmojiCategory $category)
    {
        if ($category->emojis()->count() > 0) {
            return back()->with('error', 'Нельзя удалить категорию со смайликами');
        }

        $category->delete();
        return back()->with('success', 'Категория успешно удалена');
    }
}
