<?php

// php artisan make:command RecalculateCounters

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Topic;
use App\Models\Post;

class RecalculateCounters extends Command
{
    protected $signature = 'counters:recalculate {--topics : Recalculate topic counters} {--all : Recalculate all counters}';
    protected $description = 'Recalculate reply counts, likes counts and other counters';

    public function handle()
    {
        if ($this->option('topics') || $this->option('all')) {
            $this->recalculateTopicCounters();
        }
        
        return 0;
    }

    private function recalculateTopicCounters()
    {
        $this->info('Пересчитываем счетчики тем...');
        
        $topics = Topic::all();
        $bar = $this->output->createProgressBar($topics->count());
        
        foreach ($topics as $topic) {
            // Пересчитываем replies_count
            $repliesCount = $topic->posts()->count();
            
            // Пересчитываем likes_count  
            $likesCount = $topic->likes()->count();
            
            // Обновляем без событий модели
            $topic->updateQuietly([
                'replies_count' => $repliesCount,
                'likes_count' => $likesCount,
                'last_activity_at' => $topic->posts()->latest()->first()?->created_at ?? $topic->created_at
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nПересчет завершен для {$topics->count()} тем.");
    }
}