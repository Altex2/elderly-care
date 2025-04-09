<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $timezoneOffset = $request->header('Timezone-Offset', 0);
        $userNow = now()->addMinutes($timezoneOffset);

        $reminders = $request->user()->assignedReminders()
            ->where('status', 'active')
            ->orderBy('next_occurrence')
            ->get()
            ->map(function ($reminder) use ($userNow) {
                return [
                    'id' => $reminder->id,
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'priority' => $reminder->priority,
                    'next_occurrence' => $reminder->next_occurrence,
                    'is_overdue' => $reminder->next_occurrence < $userNow,
                    'completed' => $reminder->completed,
                    'completed_at' => $reminder->completed_at
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reminders
        ]);
    }

    public function complete(Request $request, Reminder $reminder)
    {
        if ($reminder->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $reminder->completed = true;
        $reminder->completed_at = now();
        $reminder->save();

        // Calculate next occurrence if it's a recurring reminder
        $nextOccurrence = $reminder->calculateNextOccurrence();
        if ($nextOccurrence) {
            $newReminder = $reminder->replicate();
            $newReminder->completed = false;
            $newReminder->completed_at = null;
            $newReminder->next_occurrence = $nextOccurrence;
            $newReminder->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder completed successfully'
        ]);
    }
} 