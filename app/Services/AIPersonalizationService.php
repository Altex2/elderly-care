<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Carbon\Carbon;

class AIPersonalizationService
{
    public function analyzeIntent($transcription)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant helping elderly users with their daily tasks and medications. Parse their requests and return a JSON object with intent type and parameters.'
                ],
                [
                    'role' => 'user',
                    'content' => $transcription
                ]
            ],
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object']
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    public function optimizeReminderSchedule(Reminder $reminder)
    {
        $logs = $reminder->logs()
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        // Analyze completion patterns
        $completionTimes = $logs->pluck('completed_at')
            ->filter()
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            });

        if ($completionTimes->isEmpty()) {
            return null;
        }

        // Calculate the most common completion time
        $timeFrequency = array_count_values($completionTimes->toArray());
        arsort($timeFrequency);
        $optimalTime = key($timeFrequency);

        // Analyze success rate at different times
        $successRate = $this->calculateSuccessRate($logs);

        // If success rate is below threshold, adjust the schedule
        if ($successRate < 0.7) { // 70% threshold
            return $this->generateNewSchedule($reminder, $optimalTime);
        }

        return null;
    }

    protected function calculateSuccessRate($logs)
    {
        if ($logs->isEmpty()) {
            return 0;
        }

        $completed = $logs->filter(function ($log) {
            return $log->status === 'completed';
        })->count();

        return $completed / $logs->count();
    }

    protected function generateNewSchedule(Reminder $reminder, string $optimalTime)
    {
        // Convert CRON or text schedule to new optimal time
        $currentSchedule = $reminder->schedule;

        // If it's a CRON expression, modify the time components
        if (preg_match('/^[0-9*\/\-,]+ [0-9*\/\-,]+ [0-9*\/\-,]+ [0-9*\/\-,]+ [0-9*\/\-,]+$/', $currentSchedule)) {
            list($hour, $minute) = explode(':', $optimalTime);
            $parts = explode(' ', $currentSchedule);
            $parts[0] = $minute; // Minute
            $parts[1] = $hour;   // Hour
            return implode(' ', $parts);
        }

        // If it's a human-readable schedule, adjust the time
        return preg_replace('/\b\d{1,2}:\d{2}\b/', $optimalTime, $currentSchedule);
    }

    public function suggestReminders($userId)
    {
        $logs = ReminderLog::where('user_id', $userId)
            ->with('reminder')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $patterns = $this->analyzePatterns($logs);

        return $this->generateSuggestions($patterns);
    }

    protected function analyzePatterns($logs)
    {
        $patterns = [];

        foreach ($logs as $log) {
            $dayOfWeek = Carbon::parse($log->completed_at)->dayOfWeek;
            $timeSlot = Carbon::parse($log->completed_at)->format('H');

            $key = "$dayOfWeek-$timeSlot";
            if (!isset($patterns[$key])) {
                $patterns[$key] = [];
            }

            $patterns[$key][] = [
                'reminder_id' => $log->reminder_id,
                'title' => $log->reminder->title,
                'status' => $log->status
            ];
        }

        return $patterns;
    }

    protected function generateSuggestions($patterns)
    {
        $suggestions = [];

        foreach ($patterns as $timeSlot => $activities) {
            $frequency = collect($activities)->countBy('reminder_id');
            $successRate = collect($activities)->countBy('status');

            // If an activity happens regularly but isn't scheduled
            foreach ($frequency as $reminderId => $count) {
                if ($count >= 3) { // Activity happened at least 3 times in same time slot
                    $activity = collect($activities)->firstWhere('reminder_id', $reminderId);
                    $suggestions[] = [
                        'title' => $activity['title'],
                        'time_slot' => $timeSlot,
                        'confidence' => ($count / 30) * 100 // Confidence based on frequency
                    ];
                }
            }
        }

        return $suggestions;
    }
}
