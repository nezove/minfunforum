<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Общие вопросы',
                'description' => 'Обсуждение общих тем и вопросов',
                'icon' => 'chat-dots',
                'sort_order' => 1,
            ],
            [
                'name' => 'Программирование',
                'description' => 'Вопросы по программированию, коду и разработке',
                'icon' => 'code-slash',
                'sort_order' => 2,
            ],
            [
                'name' => 'Веб-разработка',
                'description' => 'HTML, CSS, JavaScript, фреймворки',
                'icon' => 'globe',
                'sort_order' => 3,
            ],
            [
                'name' => 'База данных',
                'description' => 'MySQL, PostgreSQL, MongoDB и другие СУБД',
                'icon' => 'server',
                'sort_order' => 4,
            ],
            [
                'name' => 'Laravel',
                'description' => 'Вопросы по фреймворку Laravel',
                'icon' => 'gear',
                'sort_order' => 5,
            ],
            [
                'name' => 'Помощь новичкам',
                'description' => 'Раздел для начинающих разработчиков',
                'icon' => 'question-circle',
                'sort_order' => 6,
            ],
            [
                'name' => 'Проекты и портфолио',
                'description' => 'Демонстрация проектов и получение обратной связи',
                'icon' => 'folder',
                'sort_order' => 7,
            ],
            [
                'name' => 'Freelance и работа',
                'description' => 'Обсуждение фриланса, поиска работы и карьеры',
                'icon' => 'briefcase',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}