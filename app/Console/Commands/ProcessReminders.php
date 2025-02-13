<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReminderSchedulerService;

class ProcessReminders extends Command
{
    protected $signature = 'reminders:process';
    protected $description = 'Process scheduled reminders';

    protected $scheduler;

    public function __construct(ReminderSchedulerService $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    public function handle()
    {
        $this->info('Processing scheduled reminders...');
        $this->scheduler->processScheduledReminders();
        $this->info('Completed processing reminders.');
    }
}
