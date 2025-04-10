<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reminders:process')->everyMinute();
        $schedule->command('reminders:check-missed')->everyFiveMinutes();
        $schedule->call(function () {
            app(\App\Http\Controllers\ReminderController::class)->checkMissedReminders();
        })->hourly();

        // Reset daily activities at midnight
        $schedule->command('activities:reset')
            ->daily()
            ->at('00:00')
            ->timezone('Europe/Bucharest');

        // Update daily reminders at midnight
        $schedule->command('reminders:update-daily')
            ->daily()
            ->at('00:00')
            ->timezone('Europe/Bucharest');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        Commands\ProcessReminders::class,
        Commands\CheckMissedReminders::class,
    ];
}
