<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;

class CleanBanNotifications extends Command
{
    protected $signature = 'ban:clean-notifications';
    protected $description = 'Удаляет уведомления о блокировке для разблокированных пользователей';

    public function handle()
    {
        // Находим разблокированных пользователей с уведомлениями о блокировке
        $affectedUsers = User::whereHas('notifications', function($query) {
            $query->whereIn('type', ['temporary_ban', 'permanent_ban']);
        })->where('is_banned', false)->get();

        $count = 0;
        foreach ($affectedUsers as $user) {
            $deletedCount = $user->notifications()
                ->whereIn('type', ['temporary_ban', 'permanent_ban'])
                ->delete();
            $count += $deletedCount;
        }

        $this->info("Удалено {$count} уведомлений о блокировке для разблокированных пользователей.");
    }
}
