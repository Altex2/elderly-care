<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\VoiceService;
use App\Services\AIPersonalizationService;
use App\Services\VoiceAgentService;
use App\Services\VoiceCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class VoiceController extends Controller
{
    protected $voiceService;
    protected $aiService;
    protected $voiceAgentService;
    protected $voiceCommandService;

    public function __construct(VoiceService $voiceService, AIPersonalizationService $aiService, VoiceAgentService $voiceAgentService, VoiceCommandService $voiceCommandService)
    {
        $this->voiceService = $voiceService;
        $this->aiService = $aiService;
        $this->voiceAgentService = $voiceAgentService;
        $this->voiceCommandService = $voiceCommandService;
    }

    public function index()
    {
        $reminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->orderBy('next_occurrence')
            ->get();

        // Don't pass testing mode by default
        return view('voice.interface', compact('reminders'));
    }

    public function processVoice(Request $request)
    {
        $command = strtolower($request->input('command'));
        // Get client's timezone offset in minutes
        $timezoneOffset = $request->input('timezone_offset', 0);

        // Adjust current time based on client's timezone
        $userNow = now()->addMinutes($timezoneOffset);

        // List reminders for a specific time period
        if (preg_match('/(what|show|list).*(reminders?|tasks?).*(?:for|in|during)\s+(today|tomorrow|this week|next week|this month|next month)/', $command, $matches)) {
            return $this->listRemindersForPeriod($matches[3], $userNow);
        }

        // List reminders by priority
        if (preg_match('/(what|show|list).*(priority|important|urgent).*reminders?/', $command)) {
            return $this->listPriorityReminders();
        }

        // List overdue reminders
        if (str_contains($command, 'overdue') || str_contains($command, 'missed')) {
            return $this->listOverdueReminders($request);
        }

        // Process existing commands
        if (str_contains($command, 'list') || str_contains($command, 'show')) {
            return $this->listReminders();
        }

        if (str_contains($command, 'complete') || str_contains($command, 'done') || str_contains($command, 'finished')) {
            return $this->completeReminder($command);
        }

        if (str_contains($command, 'next') || str_contains($command, "what's next")) {
            return $this->getNextReminder();
        }

        return response()->json([
            'success' => false,
            'message' => "I didn't understand that command. Try saying:
                        'list reminders',
                        'show reminders for this week',
                        'list priority reminders',
                        'show overdue reminders',
                        'complete [reminder name]', or
                        'what's next?'"
        ]);
    }

    private function listRemindersForPeriod($period, $userNow)
    {
        $start = $userNow->copy()->startOfDay();
        $end = match($period) {
            'today' => $userNow->copy()->endOfDay(),
            'tomorrow' => $userNow->copy()->addDay()->endOfDay(),
            'this week' => $userNow->copy()->endOfWeek(),
            'next week' => $userNow->copy()->addWeek()->endOfWeek(),
            'this month' => $userNow->copy()->endOfMonth(),
            'next month' => $userNow->copy()->addMonth()->endOfMonth(),
            default => $userNow->copy()->endOfDay()
        };

        $reminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->whereBetween('next_occurrence', [$start, $end])
            ->orderBy('next_occurrence')
            ->get();

        if ($reminders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => "You have no reminders scheduled for $period."
            ]);
        }

        $message = "Here are your reminders for $period: ";
        foreach ($reminders as $reminder) {
            $time = $reminder->next_occurrence->format('g:i A');
            $message .= "{$reminder->title} at {$time}, ";
        }

        return response()->json([
            'success' => true,
            'message' => rtrim($message, ', ')
        ]);
    }

    private function listPriorityReminders()
    {
        $reminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->where('priority', '>=', 3)
            ->orderBy('priority', 'desc')
            ->orderBy('next_occurrence')
            ->get();

        if ($reminders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => "You have no high-priority reminders."
            ]);
        }

        $message = "Here are your priority reminders: ";
        foreach ($reminders as $reminder) {
            $message .= "Priority {$reminder->priority}: {$reminder->title}, ";
        }

        return response()->json([
            'success' => true,
            'message' => rtrim($message, ', ')
        ]);
    }

    private function listOverdueReminders(Request $request)
    {
        $userNow = now()->addMinutes($request->input('timezone_offset', 0));

        $reminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->where('next_occurrence', '<', $userNow)
            ->orderBy('next_occurrence')
            ->get();

        if ($reminders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => "You have no overdue reminders."
            ]);
        }

        $message = "Here are your overdue reminders: ";
        foreach ($reminders as $reminder) {
            // Convert reminder time to user's timezone
            $reminderTime = $reminder->next_occurrence->addMinutes($request->input('timezone_offset', 0));
            $timeOverdue = $userNow->diffForHumans($reminderTime, [
                'parts' => 2,  // Show 2 most significant parts (e.g., "2 hours 33 minutes")
                'join' => true, // Join parts with "and"
                'short' => false // Use full words instead of abbreviations
            ]);
            $message .= "{$reminder->title} (overdue by {$timeOverdue}), ";
        }

        return response()->json([
            'success' => true,
            'message' => rtrim($message, ', ')
        ]);
    }

    private function listReminders()
    {
        $userNow = now()->addMinutes(request()->input('timezone_offset', 0));

        $reminders = auth()->user()->assignedReminders()
            ->orderBy('next_occurrence')
            ->get();

        if ($reminders->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nu aveți memento-uri active.'
            ]);
        }

        $message = "Memento-urile dvs. sunt: ";
        foreach ($reminders as $reminder) {
            $reminderTime = $reminder->next_occurrence->addMinutes(request()->input('timezone_offset', 0));
            $timeUntil = $userNow->diffForHumans($reminderTime, [
                'parts' => 2,
                'join' => true,
                'short' => false
            ]);
            $message .= "{$reminder->title} {$timeUntil}, ";
        }

        return response()->json([
            'success' => true,
            'message' => rtrim($message, ', ')
        ]);
    }

    private function completeReminder($command)
    {
        // Extract the reminder title from the command
        // Remove common phrases to isolate the reminder name
        $commonPhrases = ['complete', 'done', 'finished', 'mark', 'as', 'complete', 'with'];
        $reminderTitle = $command;
        foreach ($commonPhrases as $phrase) {
            $reminderTitle = str_replace($phrase, '', $reminderTitle);
        }
        $reminderTitle = trim($reminderTitle);

        $reminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->get();

        foreach ($reminders as $reminder) {
            if (str_contains(strtolower($reminder->title), $reminderTitle)) {
                // Use Romania timezone
                $userNow = now()->setTimezone('Europe/Bucharest');
                
                // Mark as completed in pivot table
                $reminder->users()->updateExistingPivot(auth()->id(), [
                    'completed' => true,
                    'completed_at' => $userNow
                ]);

                // Calculate next occurrence based on frequency
                if ($reminder->frequency !== 'once') {
                    $originalTime = Carbon::parse($reminder->start_date);
                    
                    switch ($reminder->frequency) {
                        case 'daily':
                            $nextDate = $userNow->copy()->addDay();
                            break;
                        case 'weekly':
                            $nextDate = $userNow->copy()->addWeek();
                            break;
                        case 'monthly':
                            $nextDate = $userNow->copy()->addMonth();
                            break;
                        case 'yearly':
                            $nextDate = $userNow->copy()->addYear();
                            break;
                        default:
                            $nextDate = null;
                    }

                    if ($nextDate) {
                        // Keep the original hour and minute
                        $nextDate->setTime($originalTime->hour, $originalTime->minute);
                        $reminder->next_occurrence = $nextDate;
                    }
                }

                $reminder->completed = true;
                $reminder->completed_at = $userNow;
                $reminder->save();

                return response()->json([
                    'success' => true,
                    'message' => "Great! I've marked '{$reminder->title}' as completed.",
                    'refresh' => true
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => "I couldn't find a reminder matching '{$reminderTitle}'. Please try again or say 'list reminders' to hear your active reminders."
        ]);
    }

    private function getNextReminder()
    {
        $userNow = now()->addMinutes(request()->input('timezone_offset', 0));

        $nextReminder = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->orderBy('next_occurrence')
            ->first();

        if (!$nextReminder) {
            return response()->json([
                'success' => true,
                'message' => 'You have no upcoming reminders.'
            ]);
        }

        $reminderTime = $nextReminder->next_occurrence->addMinutes(request()->input('timezone_offset', 0));
        $timeUntil = $userNow->diffForHumans($reminderTime, [
            'parts' => 2,
            'join' => true,
            'short' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => "Your next reminder is {$nextReminder->title} {$timeUntil}."
        ]);
    }

    private function enableTestingMode() {
        // Add test buttons for common commands
        $testCommands = [
            'Basic Commands' => [
                'List my reminders',
                'Show my reminders'
            ],
            'Time-based Commands' => [
                'Show reminders for today',
                'Show reminders for tomorrow',
                'Show reminders for this week',
                'Show reminders for next week',
                'Show reminders for this month'
            ],
            'Status Commands' => [
                'List priority reminders',
                'Show overdue reminders',
                'Show missed reminders'
            ]
        ];

        $testingDiv = '<div class="mt-4 p-4 bg-yellow-50 rounded-lg">';
        $testingDiv .= '<h4 class="font-medium text-yellow-800 mb-4">Testing Mode (No HTTPS)</h4>';

        foreach ($testCommands as $category => $commands) {
            $testingDiv .= "<div class='mb-4'>";
            $testingDiv .= "<h5 class='text-sm font-medium text-yellow-700 mb-2'>{$category}</h5>";
            $testingDiv .= "<div class='space-y-2'>";

            foreach ($commands as $cmd) {
                $testingDiv .= "
                    <button
                        onclick='processVoiceCommand(\"" . addslashes($cmd) . "\")'
                        class='block w-full text-left px-3 py-2 bg-white hover:bg-gray-50 rounded border border-yellow-300 text-sm'>
                        Test: \"{$cmd}\"
                    </button>";
            }
            $testingDiv .= "</div></div>";
        }

        // Add custom complete reminder input
        $testingDiv .= '
            <div class="mt-6">
                <h5 class="text-sm font-medium text-yellow-700 mb-2">Complete a Specific Reminder:</h5>
                <div class="flex space-x-2">
                    <input type="text"
                           id="customReminderInput"
                           placeholder="Enter reminder name"
                           class="flex-1 px-3 py-2 bg-white rounded border border-yellow-300 text-sm">
                    <button onclick="completeCustomReminder()"
                            class="btn btn-success">
                        Complete
                    </button>
                </div>
            </div>';

        $testingDiv .= '</div>';

        return $testingDiv;
    }

    public function processCommand(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        try {
            $result = $this->voiceCommandService->processCommand($request->text, auth()->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la procesarea comenzii.'
            ], 500);
        }
    }

    public function processAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,mp3,wav|max:10240'
        ]);

        try {
            // Store the audio file temporarily
            $path = $request->file('audio')->store('temp/voice', 'public');
            
            // TODO: Implement speech-to-text conversion
            // For now, we'll use a mock response
            $text = "Reamintește-mi să iau medicamentele la ora 10";
            
            // Process the command
            $result = $this->voiceCommandService->processCommand($text, auth()->user());
            
            // Clean up the temporary file
            Storage::disk('public')->delete($path);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la procesarea înregistrării vocale.'
            ], 500);
        }
    }
}
