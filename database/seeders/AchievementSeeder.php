<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Achievement;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'Активист',
                'slug' => 'activist',
                'description' => 'Написал более 100 ответов на форуме',
                'icon' => null,
                'type' => 'auto',
                'condition_type' => 'posts_count',
                'condition_value' => 100,
                'is_active' => true,
                'display_order' => 1
            ],
            [
                'name' => 'Инициатор',
                'slug' => 'initiator',
                'description' => 'Создал 10 тем на форуме',
                'icon' => null,
                'type' => 'auto',
                'condition_type' => 'topics_count',
                'condition_value' => 10,
                'is_active' => true,
                'display_order' => 2
            ],
            [
                'name' => 'Новичок',
                'slug' => 'newbie',
                'description' => 'Написал первые 10 ответов',
                'icon' => null,
                'type' => 'auto',
                'condition_type' => 'posts_count',
                'condition_value' => 10,
                'is_active' => true,
                'display_order' => 3
            ],
            [
                'name' => 'Старожил',
                'slug' => 'veteran',
                'description' => 'Пользователь форума более 30 дней',
                'icon' => null,
                'type' => 'auto',
                'condition_type' => 'days_active',
                'condition_value' => 30,
                'is_active' => true,
                'display_order' => 4
            ],
            [
                'name' => 'Постоянный посетитель',
                'slug' => 'regular_visitor',
                'description' => 'Посещает форум регулярно в течение недели',
                'icon' => null,
                'type' => 'manual',
                'condition_type' => 'manual',
                'condition_value' => null,
                'is_active' => true,
                'display_order' => 5
            ],
            [
                'name' => 'Идейный вдохновитель',
                'slug' => 'ideas_contributor',
                'description' => 'Активно предлагает новые функции для форума',
                'icon' => null,
                'type' => 'manual',
                'condition_type' => 'manual',
                'condition_value' => null,
                'is_active' => true,
                'display_order' => 6
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }
    }
}
