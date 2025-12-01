<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Topic;

class FixPostsCountCommand extends Command
{
    protected $signature = 'forum:fix-counts';
    protected $description = 'Исправить счётчики постов и ответов у пользователей и тем';

    public function handle()
    {
        $this->info('Начинаю исправление счётчиков...');
        
        // Исправляем счётчики постов у пользователей
        $this->info('Исправляю счётчики постов у пользователей...');
        $users = User::all();
        $usersUpdated = 0;
        
        foreach ($users as $user) {
            $realPostsCount = $user->posts()->count();
            $realTopicsCount = $user->topics()->count();
            
            if ($user->posts_count !== $realPostsCount || $user->topics_count !== $realTopicsCount) {
                $user->update([
                    'posts_count' => $realPostsCount,
                    'topics_count' => $realTopicsCount
                ]);
                $usersUpdated++;
                $this->line("Обновлён пользователь {$user->username}: посты {$user->posts_count} -> {$realPostsCount}, темы {$user->topics_count} -> {$realTopicsCount}");
            }
        }
        
        // Исправляем счётчики ответов у тем
        $this->info('Исправляю счётчики ответов у тем...');
        $topics = Topic::all();
        $topicsUpdated = 0;
        
        foreach ($topics as $topic) {
            $realRepliesCount = $topic->posts()->count();
            
            if ($topic->replies_count !== $realRepliesCount) {
                $topic->update(['replies_count' => $realRepliesCount]);
                $topicsUpdated++;
                $this->line("Обновлена тема ID {$topic->id}: ответы {$topic->replies_count} -> {$realRepliesCount}");
            }
        }
        
        $this->info("Исправление завершено!");
        $this->info("Обновлено пользователей: {$usersUpdated}");
        $this->info("Обновлено тем: {$topicsUpdated}");
        
        return 0;
    }
}

// СОЗДАТЬ КОМАНДУ:
// php artisan make:command FixPostsCountCommand

// ЗАПУСТИТЬ КОМАНДУ:
// php artisan forum:fix-counts