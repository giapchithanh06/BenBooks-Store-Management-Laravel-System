<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    /**
     * Lịch chạy tự động (cần cấu hình cron/Task Scheduler gọi `php artisan schedule:run` mỗi phút).
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('motsach:ai-refresh')->dailyAt('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
