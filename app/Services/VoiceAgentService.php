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

            // Get user's reminders
            $reminders = $user->assignedReminders()
                ->where('next_occurrence', '>=', Carbon::now()->subHours(24))
                ->get();

            // Create system prompt with user's reminders
            $systemPrompt = $this->generateSystemPrompt($user, $reminders);

            // Process command with OpenAI
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $command]
                ],
                'temperature' => 0.7,
                'max_tokens' => 150
            ]);

            $aiResponse = $response->choices[0]->message->content;

            // Process the command and update reminders if needed
            $this->processCommandAndUpdateReminders($command, $user, $reminders);

            return [
                'success' => true,
                'message' => $aiResponse
            ];
        } catch (\Exception $e) {
            Log::error('Voice command processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Îmi pare rău, am întâmpinat o eroare. Vă rugăm să încercați din nou.'
            ];
        }
    }

    protected function generateSystemPrompt(User $user, $reminders): string
    {
        $reminderList = $reminders->map(function ($reminder) {
            return "- {$reminder->title} (Next: {$reminder->next_occurrence->format('H:i')})";
        })->join("\n");

        return "You are a helpful assistant for an elderly person who speaks Romanian. 
                The user's name is {$user->name}.
                
                Active reminders for today:
                {$reminderList}
                
                Respond in Romanian, be friendly and supportive. If the user is asking about reminders, 
                provide specific information about their schedule. If they're expressing concerns or 
                need help, be empathetic and offer appropriate assistance.";
    }

    protected function processCommandAndUpdateReminders(string $command, User $user, $reminders): void
    {
        // Check for completion commands
        if (preg_match('/am luat|am completat|am făcut/i', $command)) {
            foreach ($reminders as $reminder) {
                if (stripos($command, $reminder->title) !== false) {
                    $reminder->update(['completed' => true]);
                    break;
                }
            }
        }

        // Check for emergency situations
        if (preg_match('/urgent|ajutor|speriat|durere|rău/i', $command)) {
            // Log emergency situation
            Log::warning("Emergency situation detected for user {$user->id}: {$command}");
        }
    }

    protected function cleanText(string $text): string
    {
        // Remove special characters and extra whitespace
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
} 