<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AchievementController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->middleware(['auth', 'role:moderator,admin']);
        $this->achievementService = $achievementService;
    }

    public function index()
    {
        $achievements = Achievement::orderBy('display_order')->paginate(20);
        return view('moderation.achievements.index', compact('achievements'));
    }

    public function getAvailable()
    {
        $achievements = Achievement::where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'name', 'description', 'type']);

        return response()->json([
            'success' => true,
            'achievements' => $achievements
        ]);
    }

    public function create()
    {
        return view('moderation.achievements.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievements,slug',
            'description' => 'required|string',
            'type' => 'required|in:auto,manual',
            'condition_type' => 'nullable|in:posts_count,topics_count,days_active,manual',
            'condition_value' => 'nullable|integer|min:1',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg,webp|max:2048',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validated['type'] === 'auto' && (!$validated['condition_type'] || !$validated['condition_value'])) {
            return back()->withErrors(['condition_type' => 'Для автоматических наград необходимо указать тип условия и значение'])->withInput();
        }

        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = 'achievement_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('achievements', $filename, 'public');
            $validated['icon'] = $path;
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['display_order'] = $validated['display_order'] ?? Achievement::max('display_order') + 1;

        Achievement::create($validated);

        return redirect()->route('moderation.achievements.index')
            ->with('success', 'Награда успешно создана');
    }

    public function edit(Achievement $achievement)
    {
        return view('moderation.achievements.edit', compact('achievement'));
    }

    public function update(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:achievements,slug,' . $achievement->id,
            'description' => 'required|string',
            'type' => 'required|in:auto,manual',
            'condition_type' => 'nullable|in:posts_count,topics_count,days_active,manual',
            'condition_value' => 'nullable|integer|min:1',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg,gif,svg,webp|max:2048',
            'display_order' => 'nullable|integer|min:0',
        ]);

        if ($validated['type'] === 'auto' && (!$validated['condition_type'] || !$validated['condition_value'])) {
            return back()->withErrors(['condition_type' => 'Для автоматических наград необходимо указать тип условия и значение'])->withInput();
        }

        if ($request->hasFile('icon')) {
            if ($achievement->icon) {
                Storage::disk('public')->delete($achievement->icon);
            }
            $file = $request->file('icon');
            $filename = 'achievement_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('achievements', $filename, 'public');
            $validated['icon'] = $path;
        }

        $validated['is_active'] = $request->has('is_active');

        $achievement->update($validated);

        return redirect()->route('moderation.achievements.index')
            ->with('success', 'Награда успешно обновлена');
    }

    public function destroy(Achievement $achievement)
    {
        if ($achievement->icon) {
            Storage::disk('public')->delete($achievement->icon);
        }

        $achievement->delete();

        return redirect()->route('moderation.achievements.index')
            ->with('success', 'Награда успешно удалена');
    }

    public function award(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $this->achievementService->awardAchievement($user, $achievement, auth()->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Награда успешно выдана пользователю'
            ]);
        }

        return back()->with('success', 'Награда успешно выдана пользователю');
    }

    public function revoke(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $this->achievementService->removeAchievement($user, $achievement);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Награда успешно отозвана'
            ]);
        }

        return back()->with('success', 'Награда успешно отозвана');
    }

    public function checkAll()
    {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            $before = $user->achievements()->count();
            $this->achievementService->checkAndAwardAutoAchievements($user);
            $after = $user->achievements()->count();
            $count += ($after - $before);
        }

        return redirect()->route('moderation.achievements.index')
            ->with('success', "Проверка завершена. Выдано наград: {$count}");
    }
}
