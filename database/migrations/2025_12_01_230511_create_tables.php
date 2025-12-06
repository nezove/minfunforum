<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('allow_gallery')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->timestamps();
        });

        // 2. Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('role', ['user', 'moderator', 'admin'])->default('user');
            $table->boolean('is_banned')->default(false);
            $table->timestamp('banned_at')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->string('ban_reason')->nullable();
            $table->unsignedBigInteger('banned_by')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->integer('rating')->default(0);
            $table->integer('topics_count')->default(0);
            $table->integer('posts_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->text('username_style')->nullable(); // CSS стили для никнейма
            $table->boolean('username_style_enabled')->default(false); // Включены ли стили никнейма

            $table->index('last_activity_at');
            $table->index('role');
            $table->index(['is_banned', 'banned_until']);
        });

        // Foreign key для users.banned_by
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('banned_by')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Topics
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->integer('views_count')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->boolean('is_locked')->default(false);
            $table->integer('views')->default(0);
            $table->integer('replies_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->enum('pin_type', ['none', 'category', 'global'])->default('none');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->timestamp('edited_at')->nullable();
            $table->integer('edit_count')->default(0);
            
            $table->index('views_count');
            $table->index('replies_count');
            $table->index('last_activity_at');
            $table->index('is_closed');
            $table->index('pin_type');
        });

        // 4. Posts
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->longText('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('reply_to_post_id')->nullable();
            $table->longText('quoted_content')->nullable();
            $table->timestamps();
            $table->timestamp('edited_at')->nullable();
            $table->integer('edit_count')->default(0);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('posts')->onDelete('cascade');
            $table->foreign('reply_to_post_id')->references('id')->on('posts')->onDelete('set null');
        });

        // 5. Tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->string('color', 7)->default('#007bff');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->integer('topics_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'is_active']);
            $table->index('slug');
        });

        // 6. Topic_Tag
        Schema::create('topic_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['topic_id', 'tag_id']);
            $table->index('topic_id');
            $table->index('tag_id');
        });

        // 7. Likes
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('likeable_type');
            $table->unsignedBigInteger('likeable_id');
            $table->timestamps();
            
            $table->unique(['user_id', 'likeable_id', 'likeable_type']);
            $table->index(['likeable_type', 'likeable_id']);
            $table->index(['likeable_id', 'likeable_type']);
            $table->index(['user_id', 'likeable_type', 'likeable_id', 'created_at']);
        });

        // 8. Bookmarks
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'topic_id']);
        });

        // 9. Topic Files
        Schema::create('topic_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('mime_type');
            $table->timestamps();
        });

        // 10. Topic Gallery Images
        Schema::create('topic_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('thumbnail_path');
            $table->string('original_name');
            $table->text('description')->nullable();
            $table->bigInteger('file_size');
            $table->integer('width');
            $table->integer('height');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['topic_id', 'sort_order']);
        });

        // 11. Post Files
        Schema::create('post_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->timestamps();
            
            $table->index('post_id');
        });

        // 12. Conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_one_id', 'user_two_id']);
            $table->index('last_message_at');
        });

        // 13. Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'is_read']);
        });

        // 14. Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->text('direct_link')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'type', 'is_read']);
            $table->index('created_at');
        });

        // 15. Temporary Files
        Schema::create('temporary_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->string('filename');
            $table->string('original_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->string('file_type', 20)->default('topic');
            $table->string('thumbnail_path')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('expires_at');
            $table->index(['user_id', 'expires_at']);
            $table->index('session_id');
        });

        // 16. Drafts
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_token', 100);
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->json('tags')->nullable();
            $table->json('temporary_files')->nullable();
            $table->enum('form_type', ['topic', 'post'])->default('topic');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamp('expires_at')->useCurrent();
            $table->timestamps();
            
            $table->index('expires_at');
            $table->index(['user_id', 'expires_at']);
        });

        Schema::table('drafts', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('topics')->onDelete('cascade');
        });

        // 17. User Sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('session_type');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['user_id', 'created_at']);
            $table->index('ip_address');
        });

        // 18. Login Attempts
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('email')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->index(['ip_address', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
        });

        // 19. Emoji Categories (Категории смайликов)
        Schema::create('emoji_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название категории (например: "Эмоции", "Животные")
            $table->string('slug')->unique(); // URL-friendly название
            $table->string('icon')->nullable(); // Иконка категории (эмодзи)
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->boolean('is_active')->default(true); // Активна ли категория
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });

        // 20. Emojis (Смайлики/стикеры)
        Schema::create('emojis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('emoji_categories')->onDelete('cascade'); // Категория
            $table->string('name'); // Название смайлика
            $table->string('file_path'); // Путь к файлу (изображение/гиф)
            $table->text('keywords'); // Ключевые слова через запятую (например: "радость,счастье,улыбка")
            $table->string('file_type')->default('image'); // Тип файла: image, gif, svg
            $table->integer('width')->default(24); // Ширина в пикселях
            $table->integer('height')->default(24); // Высота в пикселях
            $table->integer('sort_order')->default(0); // Порядок сортировки
            $table->boolean('is_active')->default(true); // Активен ли смайлик
            $table->integer('usage_count')->default(0); // Счетчик использований
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('usage_count');
            $table->index('is_active');
        });

        // Вставка начальных данных
        $this->insertInitialData();
    }

    private function insertInitialData(): void
    {
        $now = now();

        // Создаём категории
        DB::table('categories')->insert([
            [
                'id' => 1,
                'name' => 'Общее',
                'description' => 'Общие темы и обсуждения',
                'icon' => 'bi-chat-dots',
                'sort_order' => 1,
                'allow_gallery' => 0,
                'seo_title' => null,
                'seo_description' => null,
                'seo_keywords' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Жизнь форума',
                'description' => 'Всё о развитии и функционировании нашего сообщества. Сообщайте о проблемах, предлагайте улучшения и делитесь идеями для развития форума.',
                'icon' => 'bi-chat-heart',
                'sort_order' => 2,
                'allow_gallery' => 1,
                'seo_title' => 'Жизнь форума',
                'seo_description' => 'Обсуждение развития форума, сообщения о багах, предложения новых функций и улучшений.',
                'seo_keywords' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Программирование',
                'description' => 'Обсуждение языков программирования, фреймворков и технологий',
                'icon' => 'bi-braces-asterisk',
                'sort_order' => 3,
                'allow_gallery' => 1,
                'seo_title' => null,
                'seo_description' => null,
                'seo_keywords' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Создаём пользователей
        // Админ: admin / 724895
        DB::table('users')->insert([
            'id' => 1,
            'username' => 'admin',
            'name' => 'Администратор',
            'email' => 'admin@forum.local',
            'email_verified_at' => $now,
            'role' => 'admin',
            'password' => Hash::make('724895'),
            'topics_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Пользователь: user / 391528
        DB::table('users')->insert([
            'id' => 2,
            'username' => 'user',
            'name' => 'Пользователь',
            'email' => 'user@forum.local',
            'email_verified_at' => $now,
            'role' => 'user',
            'password' => Hash::make('391528'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Создаём категории смайликов
        DB::table('emoji_categories')->insert([
            [
                'id' => 1,
                'name' => 'Эмоции',
                'slug' => 'emotions',
                'icon' => '😀',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Жесты',
                'slug' => 'gestures',
                'icon' => '👍',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Разное',
                'slug' => 'misc',
                'icon' => '⭐',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Создаём приветственную тему
        DB::table('topics')->insert([
            'id' => 1,
            'title' => 'Добро пожаловать на форум!',
            'content' => '<h2>Добро пожаловать на наш форум!</h2>

<p>Рады приветствовать вас в нашем сообществе — современном форуме, построенном на <strong>Laravel</strong>, одном из самых популярных PHP-фреймворков.</p>

<h3>🎯 Цель создания форума</h3>

<p>Наш форум создан для того, чтобы объединить людей, интересующихся:</p>
<ul>
<li><strong>Веб-разработкой</strong> и современными технологиями</li>
<li><strong>Laravel</strong> и PHP-программированием</li>
<li><strong>Обменом знаниями</strong> и опытом в IT-сфере</li>
<li><strong>Решением проблем</strong> и поиском ответов на вопросы</li>
</ul>

<h3>⚡ Почему Laravel?</h3>

<p>Мы выбрали Laravel в качестве основы для нашего форума по нескольким причинам:</p>
<ul>
<li><strong>Современность</strong> — Laravel использует лучшие практики веб-разработки</li>
<li><strong>Безопасность</strong> — встроенная защита от большинства веб-уязвимостей</li>
<li><strong>Производительность</strong> — быстрая работа и оптимизация</li>
<li><strong>Масштабируемость</strong> — легко расширяется при росте сообщества</li>
<li><strong>Элегантный код</strong> — чистая и понятная архитектура</li>
</ul>

<h3>🚀 Возможности форума</h3>

<p>Наш форум предоставляет:</p>
<ul>
<li>📝 <strong>Создание тем и обсуждений</strong> в различных категориях</li>
<li>💬 <strong>Комментарии и ответы</strong> с поддержкой цитирования</li>
<li>📎 <strong>Прикрепление файлов</strong> и изображений к сообщениям</li>
<li>🖼️ <strong>Галереи изображений</strong> для некоторых категорий</li>
<li>🏷️ <strong>Система тегов</strong> для удобной навигации</li>
<li>⭐ <strong>Лайки и закладки</strong> для интересных тем</li>
<li>🔔 <strong>Уведомления</strong> о новых ответах и активности</li>
<li>💭 <strong>Личные сообщения</strong> между пользователями</li>
<li>👤 <strong>Профили пользователей</strong> с аватарами и статистикой</li>
</ul>

<h3>👥 Правила сообщества</h3>

<p>Чтобы наше сообщество оставалось дружелюбным и полезным, просим соблюдать простые правила:</p>
<ul>
<li>✅ Будьте вежливы и уважайте других участников</li>
<li>✅ Создавайте темы в подходящих категориях</li>
<li>✅ Используйте поиск перед созданием новой темы</li>
<li>✅ Делитесь полезной информацией и опытом</li>
<li>❌ Не размещайте спам и рекламу</li>
<li>❌ Не используйте нецензурную лексику</li>
</ul>

<h3>💡 Начало работы</h3>

<p>Для начала работы на форуме:</p>
<ol>
<li>Заполните свой профиль и добавьте аватар</li>
<li>Ознакомьтесь с существующими категориями</li>
<li>Представьтесь в разделе "Знакомства" (если такой есть)</li>
<li>Задавайте вопросы или делитесь знаниями</li>
<li>Участвуйте в обсуждениях и помогайте другим</li>
</ol>

<h3>🛠️ О технической стороне</h3>

<p>Наш форум — это пример того, что можно создать на Laravel:</p>
<ul>
<li>Использованы <strong>Eloquent ORM</strong> для работы с базой данных</li>
<li>Применены <strong>Blade-шаблоны</strong> для рендеринга страниц</li>
<li>Реализована <strong>система авторизации</strong> и ролей</li>
<li>Настроена <strong>система кеширования</strong> для производительности</li>
<li>Используются <strong>очереди</strong> для отправки уведомлений</li>
</ul>

<h3>📧 Обратная связь</h3>

<p>Если у вас есть предложения по улучшению форума или вы нашли ошибку, обязательно сообщите об этом в разделе "Жизнь форума". Мы постоянно работаем над улучшением платформы и ценим обратную связь от сообщества!</p>

<hr>

<p><em>Желаем вам приятного общения и полезных знакомств! 🎉</em></p>',
            'user_id' => 1,
            'category_id' => 2,
            'views_count' => 0,
            'views' => 0,
            'replies_count' => 0,
            'likes_count' => 0,
            'last_activity_at' => $now,
            'pin_type' => 'global',
            'is_closed' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('emojis');
        Schema::dropIfExists('emoji_categories');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('drafts');
        Schema::dropIfExists('temporary_files');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('post_files');
        Schema::dropIfExists('topic_gallery_images');
        Schema::dropIfExists('topic_files');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('likes');
        Schema::dropIfExists('topic_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('topics');
        Schema::dropIfExists('users');
        Schema::dropIfExists('categories');
    }
};