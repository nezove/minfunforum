<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
{
    // Обновление sitemap каждый час
    $schedule->command('sitemap:generate')
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sitemap.log'));

    // Отправка sitemap в поисковые системы раз в день в 6:00
    $schedule->command('sitemap:generate --submit')
        ->dailyAt('06:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/sitemap-submit.log'));

    // Очистка старых логов sitemap раз в неделю
    $schedule->call(function () {
        $logFile = storage_path('logs/sitemap.log');
        if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) { // 10MB
            file_put_contents($logFile, '');
        }
        
        $submitLogFile = storage_path('logs/sitemap-submit.log');
        if (file_exists($submitLogFile) && filesize($submitLogFile) > 10 * 1024 * 1024) {
            file_put_contents($submitLogFile, '');
        }
    })->weekly();
}
protected $commands = [
    Commands\CleanupOrphanedFiles::class,
];
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
