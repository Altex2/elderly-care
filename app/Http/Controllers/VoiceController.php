<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Services\VoiceService;
use App\Services\AIPersonalizationService;
use App\Services\VoiceAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use OpenAI\Laravel\Facades\OpenAI;

class VoiceController extends Controller
{
    protected $voiceService;
    protected $aiService;
    protected $voiceAgentService;

    public function __construct(VoiceService $voiceService, AIPersonalizationService $aiService, VoiceAgentService $voiceAgentService)
    {
        $this->voiceService = $voiceService;
        $this->aiService = $aiService;
        $this->voiceAgentService = $voiceAgentService;
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
                $reminder->completed = true;
                $reminder->completed_at = now();
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
                'What\'s next?',
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

        $testingDiv = '<div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 rounded-lg">';
        $testingDiv .= '<h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-4">Testing Mode (No HTTPS)</h4>';

        foreach ($testCommands as $category => $commands) {
            $testingDiv .= "<div class='mb-4'>";
            $testingDiv .= "<h5 class='text-sm font-medium text-yellow-700 dark:text-yellow-300 mb-2'>{$category}</h5>";
            $testingDiv .= "<div class='space-y-2'>";

            foreach ($commands as $cmd) {
                $testingDiv .= "
                    <button
                        onclick='processVoiceCommand(\"" . addslashes($cmd) . "\")'
                        class='block w-full text-left px-3 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded border border-yellow-300 dark:border-yellow-700 text-sm'>
                        Test: \"{$cmd}\"
                    </button>";
            }
            $testingDiv .= "</div></div>";
        }

        // Add custom complete reminder input
        $testingDiv .= '
            <div class="mt-6">
                <h5 class="text-sm font-medium text-yellow-700 dark:text-yellow-300 mb-2">Complete a Specific Reminder:</h5>
                <div class="flex space-x-2">
                    <input type="text"
                           id="customReminderInput"
                           placeholder="Enter reminder name"
                           class="flex-1 px-3 py-2 bg-white dark:bg-gray-800 rounded border border-yellow-300 dark:border-yellow-700 text-sm">
                    <button onclick="completeCustomReminder()"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                        Complete
                    </button>
                </div>
            </div>';

        $testingDiv .= '</div>';

        return $testingDiv;
    }

    public function processCommand(Request $request)
    {
        try {
            $request->validate([
                'text' => 'required|string'
            ]);

            $command = strtolower($request->input('text'));
            $timezoneOffset = $request->input('timezone_offset', 0);
            
            // List reminders for a specific time period
            if (preg_match('/(what|show|list|ce).*(reminders?|tasks?|de facut).*(?:for|in|during|pentru|in)?\s*(today|tomorrow|this week|next week|this month|next month|astazi|maine|saptamana asta|saptamana viitoare|luna asta|luna viitoare)?/', $command, $matches)) {
                if (!empty($matches[3])) {
                    return $this->listRemindersForPeriod($matches[3], now()->addMinutes($timezoneOffset));
                }
                return $this->listReminders();
            }

            // List reminders by priority
            if (preg_match('/(what|show|list|arata).*(priority|important|urgent|prioritate).*reminders?/', $command)) {
                return $this->listPriorityReminders();
            }

            // List overdue reminders
            if (str_contains($command, 'overdue') || str_contains($command, 'missed') || str_contains($command, 'restante')) {
                return $this->listOverdueReminders($request);
            }

            // Process existing commands
            if (str_contains($command, 'list') || str_contains($command, 'show') || str_contains($command, 'arata')) {
                return $this->listReminders();
            }

            if (str_contains($command, 'complete') || str_contains($command, 'done') || str_contains($command, 'finished') || str_contains($command, 'am facut')) {
                return $this->completeReminder($command);
            }

            if (str_contains($command, 'next') || str_contains($command, "what's next") || str_contains($command, 'urmatorul')) {
                return $this->getNextReminder();
            }

            if (str_contains($command, 'ajutor') || str_contains($command, 'help')) {
                return response()->json([
                    'success' => true,
                    'message' => "Puteți folosi următoarele comenzi:\n" .
                                "- 'ce am de facut' - pentru a vedea toate memento-urile\n" .
                                "- 'ce am de facut astazi/maine/saptamana asta' - pentru memento-uri specifice\n" .
                                "- 'arata memento-urile prioritare' - pentru memento-uri importante\n" .
                                "- 'arata memento-urile restante' - pentru memento-uri depășite\n" .
                                "- 'am facut [nume memento]' - pentru a marca un memento ca fiind completat"
                ]);
            }

            if (str_contains($command, 'ce am de facut')) {
                return $this->listReminders();
            }

            return response()->json([
                'success' => false,
                'message' => "Nu am înțeles comanda. Încercați să spuneți:\n" .
                            "'ce am de facut',\n" .
                            "'ce am de facut astazi/maine',\n" .
                            "'arata memento-urile prioritare',\n" .
                            "'arata memento-urile restante', sau\n" .
                            "'am facut [nume memento]'"
            ]);

        } catch (\Exception $e) {
            Log::error('Voice command processing error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la procesarea comenzii: ' . $e->getMessage()
            ]);
        }
    }

    public function processAudio(Request $request)
    {
        try {
            if (!$request->hasFile('audio')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nu s-a primit niciun fișier audio.'
                ]);
            }

            $audioFile = $request->file('audio');
            
            // Log the incoming file details
            Log::info('Received audio file', [
                'name' => $audioFile->getClientOriginalName(),
                'mime' => $audioFile->getMimeType(),
                'size' => $audioFile->getSize(),
                'extension' => $audioFile->getClientOriginalExtension()
            ]);

            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Save with original extension
            $extension = $audioFile->getClientOriginalExtension() ?: 'webm';
            $tempPath = $tempDir . '/' . uniqid('audio_') . '.' . $extension;
            
            // Move the uploaded file
            $audioFile->move(dirname($tempPath), basename($tempPath));

            // Verify the file exists and is readable
            if (!file_exists($tempPath) || !is_readable($tempPath)) {
                throw new \Exception('Could not save or read the audio file');
            }

            // Use OpenAI's Whisper API to transcribe the audio
            $response = OpenAI::audio()->transcribe([
                'model' => 'whisper-1',
                'file' => fopen($tempPath, 'r'),
                'language' => 'ro'
            ]);

            // Clean up the temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if (empty($response->text)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nu am putut transcrie audio-ul. Vă rugăm încercați din nou.'
                ]);
            }

            Log::info('Transcription successful', ['text' => $response->text]);

            // Create a new request with the transcribed text
            $voiceRequest = new Request();
            $voiceRequest->merge(['text' => $response->text]);

            // Process the transcribed command
            return $this->processCommand($voiceRequest);

        } catch (\OpenAI\Exceptions\ErrorException $e) {
            Log::error('OpenAI API Error:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Eroare la procesarea audio: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error('Voice Processing Error:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Eroare la procesarea audio: ' . $e->getMessage()
            ]);
        }
    }
}
