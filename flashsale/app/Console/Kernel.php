<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Redis;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        // Register your custom Artisan commands here
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
{
    // Clear Redis every minute (only for testing — not  for production)
    $schedule->call(function () {
        Redis::flushall();
    })->everyMinute();

    // Run expired hold release job every minute
    $schedule->command('flashsale:release-expired')->everyMinute();
}

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Load commands from app/Console/Commands
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
