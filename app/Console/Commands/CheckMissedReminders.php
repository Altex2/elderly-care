<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Reminder;
use App\Notifications\MissedReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckMissedReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:check-missed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for reminders that have been missed by more than 1 hour and notify caregivers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for missed reminders...');
        
        try {
            // Get all users with the role 'user' (patients)
            $patients = User::where('role', 'user')->get();
            
            $count = 0;
            
            foreach ($patients as $patient) {
                try {
                    // Get reminders that are more than 1 hour overdue
                    $missedReminders = $patient->assignedReminders()
                        ->where('status', 'active')
                        ->where('reminder_user.completed', false)
                        ->whereRaw('DATE_ADD(next_occurrence, INTERVAL 1 HOUR) < NOW()')
                        ->get();
                    
                    if ($missedReminders->isEmpty()) {
                        continue;
                    }
                    
                    // Get the patient's caregivers
                    $caregivers = $patient->caregivers()->get();
                    
                    if ($caregivers->isEmpty()) {
                        $this->warn("No caregivers found for patient {$patient->name}");
                        continue;
                    }
                    
                    foreach ($missedReminders as $reminder) {
                        try {
                            // Check if we already sent a notification for this reminder recently
                            $notificationSentKey = "reminder:{$reminder->id}:notification_sent";
                            if (cache()->has($notificationSentKey)) {
                                continue;
                            }
                            
                            // Send notification to each caregiver
                            foreach ($caregivers as $caregiver) {
                                try {
                                    if (!$reminder->next_occurrence) {
                                        $this->warn("Reminder {$reminder->id} has no next_occurrence");
                                        continue;
                                    }
                                    
                                    $caregiver->notify(new MissedReminderNotification($reminder, $patient));
                                    $count++;
                                    $this->info("Sent notification to caregiver {$caregiver->name} about missed reminder '{$reminder->title}' for patient {$patient->name}");
                                } catch (\Exception $e) {
                                    $this->error("Failed to send notification to caregiver {$caregiver->name}: {$e->getMessage()}");
                                    Log::error("Failed to send notification", [
                                        'caregiver_id' => $caregiver->id,
                                        'patient_id' => $patient->id,
                                        'reminder_id' => $reminder->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                            
                            // Mark that we've sent a notification for this reminder and don't send again for 1 hour
                            cache()->put($notificationSentKey, true, now()->addHour());
                            
                        } catch (\Exception $e) {
                            $this->error("Error processing reminder {$reminder->id}: {$e->getMessage()}");
                            Log::error("Error processing reminder", [
                                'reminder_id' => $reminder->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing patient {$patient->id}: {$e->getMessage()}");
                    Log::error("Error processing patient", [
                        'patient_id' => $patient->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->info("Finished checking. Sent {$count} notifications.");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Fatal error in CheckMissedReminders: {$e->getMessage()}");
            Log::error("Fatal error in CheckMissedReminders", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
} 