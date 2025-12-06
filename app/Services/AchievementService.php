<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Проверить и выдать автоматические награды пользователю
     */
    public function checkAndAwardAutoAchievements(User $user): void
    {
        $autoAchievements = Achievement::where('type', 'auto')
            ->where('is_active', true)
            ->get();

        foreach ($autoAchievements as $achievement) {
            // Проверяем, есть ли уже эта награда у пользователя
            if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }

            // Проверяем условие
            if ($achievement->checkCondition($user)) {
                $this->awardAchievement($user, $achievement);
            }
        }
    }

    /**
     * Выдать награду пользователю
     */
    public function awardAchievement(User $user, Achievement $achievement, ?User $awardedBy = null): void
    {
        // Проверяем, нет ли уже этой награды
        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return;
        }

        $user->achievements()->attach($achievement->id, [
            'awarded_at' => now(),
            'awarded_by' => $awardedBy?->id
        ]);
    }

    /**
     * Удалить награду у пользователя
     */
    public function removeAchievement(User $user, Achievement $achievement): void
    {
        $user->achievements()->detach($achievement->id);
    }

    /**
     * Получить все награды с информацией о получении для пользователя
     */
    public function getUserAchievementsWithStatus(User $user)
    {
        $achievements = Achievement::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return $achievements->map(function ($achievement) use ($user) {
            $userAchievement = DB::table('user_achievements')
                ->where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->first();

            return [
                'achievement' => $achievement,
                'earned' => $userAchievement !== null,
                'awarded_at' => $userAchievement?->awarded_at ?? null,
                'awarded_by' => $userAchievement?->awarded_by ?? null
            ];
        });
    }
}
