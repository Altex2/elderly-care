<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateDailyReminders extends Command
{
    protected $signature = 'reminders:update-daily';
    protected $description = 'Resets completion status for daily reminders at midnight while preserving their times';

    public function handle()
    {
        try {
            $today = Carbon::today();
            
            // Get all active reminders for today
            $reminders = Reminder::where('status', 'active')
                ->where(function($query) use ($today) {
                    $query->where(function($q) use ($today) {
                        // Daily reminders that started before or on today
                        $q->where('frequency', 'daily')
                            ->where('start_date', '<=', $today->endOfDay())
                            ->where(function($inner) use ($today) {
                                $inner->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $today->startOfDay());
                            });
                    })->orWhere(function($q) use ($today) {
                        // One-time reminders specifically for today
                        $q->whereDate('next_occurrence', $today);
                    });
                })
                ->get();

            $count = 0;
            foreach ($reminders as $reminder) {
                $currentNextOccurrence = Carbon::parse($reminder->next_occurrence);
                
                // For daily reminders that need their next_occurrence updated
                if ($reminder->frequency === 'daily' && $currentNextOccurrence->startOfDay()->lt($today)) {
                    // Keep the same time, just update the date to today
                    $originalTime = $reminder->start_date->format('H:i:s');
                    $newNextOccurrence = $today->copy()->format('Y-m-d') . ' ' . $originalTime;
                    
                    DB::transaction(function() use ($reminder, $newNextOccurrence) {
                        // Update the reminder
                        $reminder->update([
                            'next_occurrence' => $newNextOccurrence,
                            'completed' => false,
                            'completed_at' => null
                        ]);

                        // Update the pivot table
                        $reminder->users()->updateExistingPivot($reminder->users->pluck('id'), [
                            'completed' => false,
                            'completed_at' => null,
                            'skipped' => false,
                            'skip_reason' => null
                        ]);
                    });

                    $count++;
                    Log::info("Updated daily reminder", [
                        'id' => $reminder->id,
                        'title' => $reminder->title,
                        'original_time' => $originalTime,
                        'new_next_occurrence' => $newNextOccurrence
                    ]);
                }
                // For reminders that are already set for today but need completion reset
                elseif ($currentNextOccurrence->startOfDay()->eq($today)) {
                    DB::transaction(function() use ($reminder) {
                        // Only update completion status
                        $reminder->update([
                            'completed' => false,
                            'completed_at' => null
                        ]);

                        // Update the pivot table
                        $reminder->users()->updateExistingPivot($reminder->users->pluck('id'), [
                            'completed' => false,
                            'completed_at' => null,
                            'skipped' => false,
                            'skip_reason' => null
                        ]);
                    });

                    $count++;
                    Log::info("Reset completion for reminder", [
                        'id' => $reminder->id,
                        'title' => $reminder->title,
                        'next_occurrence' => $reminder->next_occurrence
                    ]);
                }
            }

            Log::info("Updated daily reminders", [
                'count' => $count,
                'date' => $today->format('Y-m-d'),
                'time' => $today->format('H:i:s')
            ]);
            $this->info("Successfully updated {$count} daily reminders.");

        } catch (\Exception $e) {
            Log::error('Error updating daily reminders:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Failed to update daily reminders: ' . $e->getMessage());
        }
    }
} 