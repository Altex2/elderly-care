<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Client;

class VoiceAgentService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->client = \OpenAI::client($this->apiKey);
    }

    public function processVoiceCommand(string $command, int $timezoneOffset, User $user): array
    {
        try {
            // Clean the command text
            $command = $this->cleanText($command);
            Log::info('Processing command:', ['command' => $command]);

            // Get current time in user's timezone
            $userNow = now()->addMinutes($timezoneOffset);

            // Check for completion commands
            if (preg_match('/am făcut|am terminat|am completat|am luat|am luat medicamentul/i', $command)) {
                return $this->handleCompletionCommand($command, $user);
            }

            // Check for help requests
            if (preg_match('/ajutor|ce pot să fac|ce pot să spun/i', $command)) {
                return $this->getHelpMessage();
            }

            // Get overdue tasks
            $overdueTasks = $user->assignedReminders()
                ->where('completed', false)
                ->where('next_occurrence', '<', $userNow)
                ->orderBy('next_occurrence')
                ->get();

            // Get today's tasks
            $todayStart = $userNow->copy()->startOfDay();
            $todayEnd = $userNow->copy()->endOfDay();
            $todayTasks = $user->assignedReminders()
                ->where('completed', false)
                ->whereBetween('next_occurrence', [$todayStart, $todayEnd])
                ->orderBy('next_occurrence')
                ->get();

            // Get completed tasks
            $completedTasks = $user->assignedReminders()
                ->where('completed', true)
                ->orderByDesc('completed_at')
                ->limit(5)
                ->get();

            // Get next upcoming task
            $nextTask = $user->assignedReminders()
                ->where('completed', false)
                ->where('next_occurrence', '>', $userNow)
                ->orderBy('next_occurrence')
                ->first();

            // Format the response message
            $message = '';

            // Handle no tasks case first
            if ($overdueTasks->isEmpty() && $todayTasks->isEmpty()) {
                if ($nextTask) {
                    $nextTaskTime = $this->formatTimeInRomanian($userNow, $nextTask->next_occurrence);
                    $message = "Nu aveți nicio sarcină pentru astăzi. Următoarea sarcină este '{$nextTask->title}' {$nextTaskTime}.";
                } else {
                    $message = "Nu aveți nicio sarcină pentru astăzi și nicio sarcină programată pentru viitor.";
                }
            } else {
                // Add overdue tasks
                if ($overdueTasks->isNotEmpty()) {
                    $message .= "\nSarcini restante:";
                    foreach ($overdueTasks as $task) {
                        $timeOverdue = $this->formatTimeInRomanian($userNow, $task->next_occurrence, true);
                        $message .= "\n- {$task->title} (restant de {$timeOverdue})";
                    }
                }

                // Add today's tasks
                if ($todayTasks->isNotEmpty()) {
                    $message .= "\n\nSarcini pentru astăzi:";
                    foreach ($todayTasks as $task) {
                        $time = $task->next_occurrence->format('H:i');
                        $timeUntil = $this->formatTimeInRomanian($userNow, $task->next_occurrence);
                        $message .= "\n- {$task->title} la ora {$time} ({$timeUntil})";
                    }
                }
            }

            // Add completed tasks if any
            if ($completedTasks->isNotEmpty()) {
                $message .= "\n\nSarcini completate recent:";
                foreach ($completedTasks as $task) {
                    $completedTime = $task->completed_at->addMinutes($timezoneOffset);
                    $completedAgo = $this->formatTimeInRomanian($userNow, $completedTime, true);
                    $message .= "\n- {$task->title} (completat acum {$completedAgo})";
                }
            }

            // Add instructions for marking tasks as complete
            if ($overdueTasks->isNotEmpty() || $todayTasks->isNotEmpty()) {
                $message .= "\n\nPuteți să-mi spuneți 'am făcut' urmat de numele sarcinii pentru a o marca ca fiind completată.";
            }

            return [
                'success' => true,
                'message' => $message
            ];

        } catch (\Exception $e) {
            Log::error('Voice command processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Îmi pare rău, am întâmpinat o eroare. Vă rugăm să încercați din nou.'
            ];
        }
    }

    protected function handleCompletionCommand(string $command, User $user): array
    {
        try {
            // Get all incomplete tasks
            $tasks = $user->assignedReminders()
                ->where('completed', false)
                ->get();

            // Log the command and available tasks for debugging
            Log::info('Completion command:', [
                'command' => $command,
                'available_tasks' => $tasks->pluck('title')->toArray(),
                'user_id' => $user->id
            ]);

            // Try to find the task by exact match first
            foreach ($tasks as $task) {
                if (stripos($command, $task->title) !== false) {
                    // Log the task before update
                    Log::info('Attempting to mark task as complete:', [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'current_completed' => $task->completed
                    ]);

                    // Update the task
                    $task->completed = true;
                    $task->completed_at = now();
                    $saved = $task->save();

                    // Log the result of the save operation
                    Log::info('Task update result:', [
                        'task_id' => $task->id,
                        'saved' => $saved,
                        'new_completed' => $task->completed,
                        'new_completed_at' => $task->completed_at
                    ]);

                    if (!$saved) {
                        Log::error('Failed to save task completion:', [
                            'task_id' => $task->id,
                            'errors' => $task->getErrors()
                        ]);
                        return [
                            'success' => false,
                            'message' => "Nu am putut marca sarcina ca fiind completată. Vă rugăm să încercați din nou."
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => "Excelent! Am marcat '{$task->title}' ca fiind completată."
                    ];
                }
            }

            // If no exact match, try partial matches
            foreach ($tasks as $task) {
                $words = explode(' ', strtolower($command));
                $taskWords = explode(' ', strtolower($task->title));
                
                $matchCount = 0;
                foreach ($words as $word) {
                    if (in_array($word, $taskWords)) {
                        $matchCount++;
                    }
                }
                
                // If more than 50% of the task words match
                if ($matchCount >= count($taskWords) * 0.5) {
                    // Log the task before update
                    Log::info('Attempting to mark task as complete (partial match):', [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'current_completed' => $task->completed,
                        'match_count' => $matchCount
                    ]);

                    // Update the task
                    $task->completed = true;
                    $task->completed_at = now();
                    $saved = $task->save();

                    // Log the result of the save operation
                    Log::info('Task update result (partial match):', [
                        'task_id' => $task->id,
                        'saved' => $saved,
                        'new_completed' => $task->completed,
                        'new_completed_at' => $task->completed_at
                    ]);

                    if (!$saved) {
                        Log::error('Failed to save task completion (partial match):', [
                            'task_id' => $task->id,
                            'errors' => $task->getErrors()
                        ]);
                        return [
                            'success' => false,
                            'message' => "Nu am putut marca sarcina ca fiind completată. Vă rugăm să încercați din nou."
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => "Excelent! Am marcat '{$task->title}' ca fiind completată."
                    ];
                }
            }

            Log::warning('No matching task found for completion command', [
                'command' => $command,
                'available_tasks' => $tasks->pluck('title')->toArray(),
                'user_id' => $user->id
            ]);

            return [
                'success' => false,
                'message' => "Nu am putut găsi sarcina menționată. Vă rugăm să încercați din nou."
            ];
        } catch (\Exception $e) {
            Log::error('Error in handleCompletionCommand: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => "A apărut o eroare la marcarea sarcinii. Vă rugăm să încercați din nou."
            ];
        }
    }

    protected function getHelpMessage(): array
    {
        return [
            'success' => true,
            'message' => "Puteți să-mi spuneți următoarele:\n" .
                "- 'ce am de făcut' pentru a vedea sarcinile dvs.\n" .
                "- 'am făcut' urmat de numele sarcinii pentru a o marca ca fiind completată\n" .
                "- 'ajutor' pentru a vedea această listă din nou"
        ];
    }

    protected function cleanText(string $text): string
    {
        // Remove special characters but keep Romanian diacritics
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    protected function formatTimeInRomanian(Carbon $now, Carbon $target, bool $isPast = false): string
    {
        $diff = $now->diff($target);
        
        if ($diff->y > 0) {
            return $diff->y . ' ' . ($diff->y == 1 ? 'an' : 'ani');
        }
        
        if ($diff->m > 0) {
            return $diff->m . ' ' . ($diff->m == 1 ? 'lună' : 'luni');
        }
        
        if ($diff->d > 0) {
            return $diff->d . ' ' . ($diff->d == 1 ? 'zi' : 'zile');
        }
        
        if ($diff->h > 0) {
            return $diff->h . ' ' . ($diff->h == 1 ? 'oră' : 'ore');
        }
        
        if ($diff->i > 0) {
            return $diff->i . ' ' . ($diff->i == 1 ? 'minut' : 'minute');
        }
        
        return $diff->s . ' ' . ($diff->s == 1 ? 'secundă' : 'secunde');
    }
} 