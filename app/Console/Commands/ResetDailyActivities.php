<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyActivity;
use Carbon\Carbon;

class ResetDailyActivities extends Command
{
    protected $signature = 'activities:reset';
    protected $description = 'Reset daily activities at midnight';

    public function handle()
    {
        // Get yesterday's date
        $yesterday = Carbon::yesterday();

        // Update all activities from yesterday to be incomplete
        DailyActivity::whereDate('date', $yesterday)
            ->update([
                'completed' => false,
                'completed_at' => null
            ]);

        $this->info('Daily activities have been reset for ' . $yesterday->format('Y-m-d'));
    }
} 