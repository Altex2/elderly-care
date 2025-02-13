<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;

class ReminderSchedulerService
{
    protected $voiceService;
    protected $aiService;

    public function __construct(VoiceService $voiceService, AIPersonalizationService $aiService)
    {
        $this->voiceService = $voiceService;
        $this->aiService = $aiService;
    }

    public function processScheduledReminders()
    {
        $now = Carbon::now();

        $reminders = Reminder::where('status', 'active')
            ->where(function ($query) use ($now) {
                // Check if the reminder should be triggered based on schedule
                $query->whereRaw("? REGEXP schedule", [$now->format('i H d m w')]);
            })
            ->with('user')
            ->get();

        foreach ($reminders as $reminder) {
            $this->sendReminder($reminder);

            // Optimize schedule based on user behavior
            $newSchedule = $this->aiService->optimizeReminderSchedule($reminder);
            if ($newSchedule) {
                $reminder->update(['schedule' => $newSchedule]);
            }
        }
    }

    protected function sendReminder(Reminder $reminder)
    {
        try {
            // Generate voice message
            $message = $this->generateReminderMessage($reminder);
            $audioResponse = $this->voiceService->synthesizeSpeech($message);

            // Store the audio file
            $filename = 'reminders/' . $reminder->id . '_' . time() . '.mp3';
            Storage::disk('public')->put($filename, $audioResponse);

            // Create notification record
            $reminder->notifications()->create([
                'user_id' => $reminder->user_id,
                'type' => 'voice',
                'content' => $message,
                'audio_file' => $filename,
                'status' => 'pending'
            ]);

            // Send push notification if user has mobile device
            if ($reminder->user->push_token) {
                $this->sendPushNotification($reminder->user, $message);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send reminder: ' . $e->getMessage(), [
                'reminder_id' => $reminder->id,
                'user_id' => $reminder->user_id
            ]);
        }
    }

    protected function generateReminderMessage(Reminder $reminder)
    {
        $timeOfDay = Carbon::now()->format('H');
        $greeting = $timeOfDay < 12 ? 'Good morning' : ($timeOfDay < 18 ? 'Good afternoon' : 'Good evening');

        return "{$greeting}! It's time for {$reminder->title}. " .
               ($reminder->description ? $reminder->description : '');
    }

    protected function sendPushNotification(User $user, string $message)
    {
        try {
            $factory = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-credentials.json'));

            $messaging = $factory->createMessaging();

            $message = [
                'notification' => [
                    'title' => 'Reminder',
                    'body' => $message
                ],
                'token' => $user->push_token,
            ];

            $messaging->send($message);

        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
        }
    }
}
