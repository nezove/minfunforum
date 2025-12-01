<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FixUserActivity extends Command
{
    protected $signature = 'users:fix-activity';
    protected $description = 'Fix last_activity_at for existing users';

    public function handle()
    {
        $this->info('Исправляем last_activity_at для существующих пользователей...');
        
        // Подсчитываем пользователей без last_activity_at
        $usersWithoutActivity = User::whereNull('last_activity_at')->count();
        
        $this->info("Найдено пользователей без last_activity_at: {$usersWithoutActivity}");
        
        if ($usersWithoutActivity > 0) {
            // Обновляем всех пользователей, используя updated_at или created_at
            $usersToUpdate = DB::table('users')
    ->whereNull('last_activity_at')
    ->select('id', 'updated_at', 'created_at')
    ->get();

$updated = 0;
foreach ($usersToUpdate as $userData) {
    $activityDate = $userData->updated_at ?? $userData->created_at;
    DB::table('users')
        ->where('id', $userData->id)
        ->update(['last_activity_at' => $activityDate]);
    $updated++;
}
                
            $this->info("Обновлено записей: {$updated}");
        }
        
        // Показываем статистику
        $onlineCount = User::online()->count();
        $recentlyActiveCount = User::recentlyActive()->count();
        
        $this->info("Сейчас онлайн: {$onlineCount}");
        $this->info("Недавно активных: {$recentlyActiveCount}");
        $this->info("Готово!");
        
        return Command::SUCCESS;
    }
}