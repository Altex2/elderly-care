<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAI\OpenAI;
use App\Models\Activity;
use App\Notifications\EmergencyNotification;
use App\Models\DailyActivity;
use Illuminate\Support\Facades\Http;

class VoiceCommandService
{
    protected $user;
    protected $audioPath;
    protected $responseText;
    protected $responseAudio;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->audioPath = storage_path('app/public/voice-responses');
        if (!file_exists($this->audioPath)) {
            mkdir($this->audioPath, 0755, true);
        }
    }

    public function processCommand(string $audioFile)
    {
        try {
            // Convert audio to text using Google Cloud Speech-to-Text
            $text = $this->convertSpeechToText($audioFile);
            Log::info('Voice command text:', ['text' => $text]);

            // Preprocess the text to fix common transcription errors
            $text = $this->preprocessTranscribedText($text);

            // Process the command
            $this->processTextCommand($text);

            // Generate response audio
            $this->generateResponseAudio();

            return [
                'success' => true,
                'text' => $this->responseText,
                'audio' => $this->responseAudio
            ];
        } catch (\Exception $e) {
            Log::error('Voice command processing error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Nu am putut procesa comanda vocală. Vă rugăm să încercați din nou.'
            ];
        }
    }

    /**
     * Preprocess transcribed text to fix common transcription errors
     * 
     * @param string $text The raw transcribed text
     * @return string The preprocessed text
     */
    protected function preprocessTranscribedText(string $text)
    {
        $original = $text;
        
        // Convert to lowercase for easier pattern matching
        $text = strtolower(trim($text));
        
        // Common transcription fixes
        $replacements = [
            // Fix "Memento 9" to "Memento nou"
            'memento 9' => 'memento nou',
            'memento noua' => 'memento nou',
            'memento no' => 'memento nou',
            'memento nouu' => 'memento nou',
            'memento nouula' => 'memento nou la',
            'memento noula' => 'memento nou la',
            
            // Fix time expressions
            'la ora opt' => 'la ora 8',
            'la ora nouă' => 'la ora 9',
            'la ora zece' => 'la ora 10',
            'la ora une' => 'la ora 11',
            'la ora doisprezece' => 'la ora 12',
            'la ora treisprezece' => 'la ora 13',
            'la ora paisprezece' => 'la ora 14',
            'la ora cincisprezece' => 'la ora 15',
            'la ora șaisprezece' => 'la ora 16',
            'la ora șaptesprezece' => 'la ora 17',
            'la ora optsprezece' => 'la ora 18',
            'la ora nouăsprezece' => 'la ora 19',
            'la ora douăzeci' => 'la ora 20',
            
            // Fix common prefix/suffix issues
            'rămâne-mi' => 'reamintește-mi',
            'ramane-mi' => 'reamintește-mi',
            'reaminte mi' => 'reamintește-mi',
            'remind me' => 'reamintește-mi',
            
            // Fix common command variations
            'ce sa fac' => 'ce am de făcut',
            'ce mai am de făcut' => 'ce am de făcut',
            'cât e ceasul' => 'ce am de făcut acum'
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $text = str_replace($pattern, $replacement, $text);
        }
        
        // Log any changes that were made
        if ($text !== strtolower(trim($original))) {
            Log::info('Preprocessed transcribed text:', [
                'original' => $original,
                'preprocessed' => $text
            ]);
        }
        
        return $text;
    }

    protected function processTextCommand(string $text)
    {
        $text = strtolower(trim($text));
        Log::info('Processing text command:', ['text' => $text]);

        try {
            $systemPrompt = "You are a Romanian voice command processor for an elderly care application.
            Your task is to classify the user's command into one of these categories and return ONLY the category name.
            
            Available Commands:
            
            Memento-uri:
            1. 'Ce am de făcut?'
            2. 'Ce am de făcut azi/mâine/săptămâna aceasta/săptămâna următoare/luna aceasta?'
            3. 'Memento nou la ora [ora] [acțiune/medicament]'
            4. 'Am luat medicamentul [nume]'
            5. 'Am făcut [activitate]'
            
            Asistență Zilnică (Completare):
            6. 'Am mâncat'
            7. 'Am făcut plimbarea'
            8. 'Am măsurat tensiunea'
            9. 'Am măsurat glicemia'
            
            Întrebări Asistență Zilnică:
            10. 'Am mâncat azi?/astăzi?'
            11. 'Am făcut plimbarea azi?/astăzi?'
            12. 'Am măsurat tensiunea azi?/astăzi?'
            13. 'Am măsurat glicemia azi?/astăzi?'
            14. 'Am luat/am făcut [acțiune/medicament] azi?/astăzi?'
            
            Urgență:
            15. 'SOS'
            16. 'Urgență'
            17. 'Am nevoie de ajutor'

            Categories to return:
            - list_reminders - For commands 1 and 2
            - new_reminder - For command 3
            - reminder_complete - For commands 4 and 5 (when NOT ending with 'azi' or 'astăzi')
            - activity_complete - For commands 6-9 (when NOT ending with 'azi' or 'astăzi')
            - activity_query - For commands 10-13 (when ending with 'azi' or 'astăzi')
            - reminder_query - For command 14 (when ending with 'azi' or 'astăzi')
            - emergency - For commands 15-17
            - unknown - If none of the above match

            Rules:
            1. If a command ends with 'azi' or 'astăzi' (with or without punctuation), it MUST be classified as a query
            2. For completions (without 'azi' or 'astăzi'), return activity_complete or reminder_complete
            3. For list commands, return list_reminders
            4. For new reminders, return new_reminder
            5. For emergency, return emergency
            6. Return ONLY the category name, nothing else

            Example inputs and their categories:
            - 'ce am de făcut mâine?' -> list_reminders
            - 'am mâncat' -> activity_complete
            - 'am mâncat azi?' -> activity_query
            - 'am mâncat azi.' -> activity_query
            - 'am mâncat astăzi?' -> activity_query
            - 'am mâncat astăzi.' -> activity_query
            - 'am luat medicamentul X' -> reminder_complete
            - 'am luat medicamentul X azi?' -> reminder_query
            - 'sos' -> emergency";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.1,
                'max_tokens' => 10
            ]);

            $commandType = trim(strtolower($response->json()['choices'][0]['message']['content']));
            Log::info('Command type determined by GPT:', ['type' => $commandType]);

            switch ($commandType) {
                case 'reminder_query':
                    if (preg_match('/(am luat|am făcut) (.*?) (azi|astăzi)[.?]?$/i', $text, $matches)) {
                        $reminderName = trim($matches[2]);
                        $this->handleReminderQuery($reminderName);
                    } else {
                        $this->responseText = 'Nu am înțeles comanda. Vă rugăm să încercați din nou.';
                    }
                    break;

                case 'activity_query':
                    if (preg_match('/(am mâncat|am făcut plimbarea|am măsurat tensiunea|am măsurat glicemia) (azi|astăzi)[.?]?$/i', $text, $matches)) {
                        $activity = $matches[1];
                        $this->handleActivityQuery($activity);
                    } else {
                        $this->responseText = 'Nu am înțeles comanda. Vă rugăm să încercați din nou.';
                    }
                    break;

                case 'activity_complete':
                    if ($text === 'am mâncat') {
                        $this->handleMealCompleted();
                    } elseif ($text === 'am făcut plimbarea') {
                        $this->handleWalkCompleted();
                    } elseif ($text === 'am măsurat tensiunea') {
                        $this->handleBloodPressureMeasured();
                    } elseif ($text === 'am măsurat glicemia') {
                        $this->handleBloodSugarMeasured();
                    } else {
                        $this->responseText = 'Nu am înțeles comanda. Vă rugăm să încercați din nou.';
                    }
                    break;

                case 'reminder_complete':
                    $this->handleCompleteReminder($text);
                    break;

                case 'list_reminders':
                    $this->handleRemindersList($text);
                    break;

                case 'new_reminder':
                    $this->handleNewReminder($text);
                    break;

                case 'emergency':
                    $this->handleEmergency();
                    break;

                default:
                    Log::warning('Unknown command pattern:', ['text' => $text]);
                    $this->responseText = 'Nu am înțeles comanda. Vă rugăm să încercați din nou.';
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error processing command:', ['error' => $e->getMessage()]);
            $this->responseText = 'Nu am putut procesa comanda. Vă rugăm să încercați din nou.';
        }
    }

    /**
     * Use OpenAI to improve command recognition by matching to known patterns
     * 
     * @param string $text The transcribed text
     * @return string The improved command text
     */
    protected function improveCommandRecognition($text)
    {
        // List of known command patterns
        $knownPatterns = [
            'ce am de făcut',
            'memento nou la ora {time} {title}',
            'am făcut {reminder}',
            'am luat {medication}',
            'am mâncat',
            'am făcut plimbarea',
            'am măsurat tensiunea',
            'am măsurat glicemia',
            'sos',
            'urgență',
            'am nevoie de ajutor'
        ];

        // Get active reminders to preserve their exact names
        $activeReminders = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->pluck('reminders.title')
            ->toArray();

        $systemPrompt = "You are a Romanian language assistant. Your task is to match transcribed text to the closest known command pattern.
        Rules:
        1. Keep medication names EXACTLY as they are spoken
        2. Only fix obvious typos in command words (e.g., 'facut' to 'făcut')
        3. Preserve all numbers and special characters
        4. Do NOT change or correct medication names
        5. Do NOT add or remove words
        6. If the text matches a known pattern, return it exactly as is
        7. If unsure, return the original text

        Known medication names: " . implode(', ', $activeReminders) . "
        
        Input: {$text}
        Output:";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.1,
                'max_tokens' => 50
            ]);

            $improvedText = trim($response->json()['choices'][0]['message']['content']);

            // Validate that the improved text is a valid command
            if ($this->isValidCommand($improvedText)) {
                Log::info('Improved command text', [
                    'original' => $text,
                    'improved' => $improvedText
                ]);
                return $improvedText;
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('Error improving command recognition', [
                'error' => $e->getMessage(),
                'text' => $text
            ]);
            return $text;
        }
    }

    protected function isValidCommand(string $text)
    {
        $validPatterns = [
            '/(sos|urgență|am nevoie de ajutor)/i',
            '/(am făcut|am luat|am mâncat|am măsurat) (.*) astăzi\?/i',
            '/am luat medicamentul (.*)/i',
            '/am făcut (.*)/i',
            '/am mâncat/i',
            '/am făcut plimbarea/i',
            '/am măsurat tensiunea/i',
            '/am măsurat glicemia/i',
            '/ce am de făcut/i',
            '/memento nou la ora (.*?) (.*)/i',
            '/(am făcut|am luat) (.*)/i'
        ];

        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    protected function handleRemindersList($text)
    {
        $userTime = $this->getUserTime();
        $text = strtolower($text ?? '');
        
        // Check for different timeframes
        $isTomorrowRequest = preg_match('/(maine|mâine)/', $text);
        $isThisWeekRequest = preg_match('/(săptămâna aceasta|saptamana aceasta|săptămâna asta|saptamana asta)/', $text);
        $isNextWeekRequest = preg_match('/(săptămâna următoare|saptamana urmatoare|săptămâna viitoare|saptamana viitoare)/', $text);
        $isThisMonthRequest = preg_match('/(luna aceasta|luna asta|lună aceasta|lună asta)/', $text);
        
        // Set the target date range based on the request
        if ($isTomorrowRequest) {
            $startDate = $userTime->copy()->addDay()->startOfDay();
            $endDate = $startDate->copy()->endOfDay();
            $dateText = 'mâine';
            $showDates = false;
        } elseif ($isThisWeekRequest) {
            $startDate = $userTime->copy()->startOfWeek();
            $endDate = $userTime->copy()->endOfWeek();
            $dateText = 'săptămâna aceasta';
            $showDates = true;
        } elseif ($isNextWeekRequest) {
            $startDate = $userTime->copy()->addWeek()->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
            $dateText = 'săptămâna următoare';
            $showDates = true;
        } elseif ($isThisMonthRequest) {
            $startDate = $userTime->copy()->startOfMonth();
            $endDate = $userTime->copy()->endOfMonth();
            $dateText = 'luna aceasta';
            $showDates = true;
        } else {
            // Default to today
            $startDate = $userTime->copy()->startOfDay();
            $endDate = $userTime->copy()->endOfDay();
            $dateText = 'astăzi';
            $showDates = false;
        }
        
        Log::info('Date range details:', [
            'user_time' => $userTime->format('Y-m-d H:i:s'),
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
            'date_text' => $dateText
        ]);
        
        // Base query for reminders
        $query = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->whereBetween('reminders.next_occurrence', [$startDate, $endDate]);

        // Only check completion status for today's reminders
        if (!$isTomorrowRequest && !$isThisWeekRequest && !$isNextWeekRequest && !$isThisMonthRequest) {
            $query->where('reminder_user.completed', false);
        }

        $reminders = $query->orderBy('reminders.next_occurrence')->get();

        Log::info('Found reminders for date range:', [
            'count' => $reminders->count(),
            'reminders' => $reminders->map(function($reminder) {
                return [
                    'id' => $reminder->id,
                    'title' => $reminder->title,
                    'next_occurrence' => $reminder->next_occurrence,
                    'status' => $reminder->status,
                    'completed' => $reminder->pivot->completed ?? null
                ];
            })->toArray()
        ]);

        if ($reminders->isEmpty()) {
            $this->responseText = "Nu aveți memento-uri active pentru {$dateText}.";
            return;
        }

        $this->responseText = "Iată memento-urile dvs. pentru {$dateText}: ";
        foreach ($reminders as $reminder) {
            $nextOccurrence = Carbon::parse($reminder->next_occurrence);
            $time = $nextOccurrence->format('H:i');
            
            if ($showDates) {
                $date = $nextOccurrence->format('d.m.Y');
                $this->responseText .= $reminder->title . ' pe ' . $date . ' la ora ' . $time . '. ';
            } else {
                $this->responseText .= $reminder->title . ' la ora ' . $time . '. ';
            }
        }
    }

    protected function handleNewReminder(string $text)
    {
        Log::info('Processing new reminder command:', ['text' => $text]);
         
        // Extract time and title from various command patterns
        $patterns = [
            '/reamintește-mi să (.+) la (.+)/i',
            '/reaminteste-mi să (.+) la (.+)/i',
            '/reaminte-mi să (.+) la (.+)/i',
            '/reaminte mi să (.+) la (.+)/i',
            '/memento nou (.+) la ora (.+)/i',
            '/memento nou la ora (\d+)[,\s]+(.+)/i',
            '/memento nou la ora (.+)[,\s]+(.+)/i',
            '/memento nou la ora (\d+)(.+)/i',
            '/memento nou,? (mâine|poimâine|săptămâna viitoare) la ora (.+) (.+)/i',
            '/memento nou la ora (.+) (mâine|poimâine|săptămâna viitoare) (.+)/i'
        ];

        $matches = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                Log::info('Pattern matched:', ['pattern' => $pattern, 'matches' => $matches]);
                break;
            }
        }

        if (!$matches || count($matches) < 3) {
            Log::warning('Failed to match reminder pattern:', ['text' => $text]);
            
            // Try simplified pattern matching as a fallback
            if (strpos($text, 'memento nou') !== false && strpos($text, 'ora') !== false) {
                $parts = explode('ora', $text, 2);
                if (count($parts) === 2) {
                    $afterOra = trim($parts[1]);
                    $timeAndTitle = preg_split('/[\s,]+/', $afterOra, 2);
                    
                    if (count($timeAndTitle) === 2) {
                        $time = trim($timeAndTitle[0]);
                        $title = trim($timeAndTitle[1]);
                        
                        // Log the extracted info
                        Log::info('Extracted using fallback:', ['time' => $time, 'title' => $title]);
                        
                        // Continue with this information
                        $matches = [0, '', $time, $title];
                    }
                }
            }
            
            // If still no matches, return error
            if (!$matches || count($matches) < 3) {
                $this->responseText = 'Nu am putut înțelege memento-ul. Vă rugăm să încercați din nou.';
                return;
            }
        }

        // Handle different match patterns
        if (strpos($text, 'memento nou') !== false) {
            Log::info('Processing memento nou pattern');

            // Check for future day mentions
            $futureDay = null;
            if (strpos($text, 'mâine') !== false) {
                $futureDay = $this->getUserTime()->addDay();
            } elseif (strpos($text, 'poimâine') !== false) {
                $futureDay = $this->getUserTime()->addDays(2);
            } elseif (strpos($text, 'săptămâna viitoare') !== false) {
                $futureDay = $this->getUserTime()->addWeek();
            }

            // Extract time and title
            if (strpos($text, 'la ora') !== false) {
                // Extract time and title more carefully
                $parts = explode('la ora', $text);
                if (count($parts) === 2) {
                    $timePart = trim($parts[1]);
                    // Split by comma if present
                    $timeTitleParts = explode(',', $timePart, 2);
                    if (count($timeTitleParts) === 2) {
                        $time = trim($timeTitleParts[0]);
                        $title = trim($timeTitleParts[1]);
                    } else {
                        // If no comma, take the first word as time and rest as title
                        $words = explode(' ', $timePart);
                        $time = array_shift($words);
                        $title = implode(' ', $words);
                    }
                } else {
                    $time = trim($matches[1]);
                    $title = trim($matches[2]);
                }
            } else {
                $title = trim($matches[1]);
                $time = trim($matches[2]);
            }
        } else {
            Log::info('Processing reaminte-mi pattern');
            $title = trim($matches[1]);
            $time = trim($matches[2]);
        }

        // Clean up time format (remove commas, dots and extra spaces)
        $time = str_replace([',', '.', ' '], '', $time);
        
        // Handle different time formats
        if (is_numeric($time) && $time >= 0 && $time <= 24) {
            // Handle numeric times (both single and double digit)
            $time = intval($time) . ':00';
            Log::info('Converted numeric time:', ['raw' => $time, 'formatted' => $time]);
        } elseif (strlen($time) === 4 && is_numeric($time)) {
            // If format is "1600", convert to "16:00"
            $time = substr($time, 0, 2) . ':' . substr($time, 2);
            Log::info('Converted 4-digit time:', ['raw' => $time, 'formatted' => $time]);
        }

        // Validate time format
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            Log::warning('Invalid time format:', ['time' => $time]);
            
            // Try to recover from common time format issues
            if (is_numeric($time)) {
                $timeInt = intval($time);
                if ($timeInt >= 0 && $timeInt <= 24) {
                    $time = $timeInt . ':00';
                    Log::info('Recovered time format:', ['original' => $time, 'fixed' => $time]);
                } else {
                    $this->responseText = 'Formatul orei nu este valid. Vă rugăm să încercați din nou.';
                    return;
                }
            } else {
                $this->responseText = 'Formatul orei nu este valid. Vă rugăm să încercați din nou.';
                return;
            }
        }

        // Use ChatGPT to check if the title might be a misspelled medication name
        $originalTitle = $title;
        $improvedTitle = $this->checkAndFixMedicationName($title);
        if ($improvedTitle !== $title) {
            Log::info('Corrected medication name:', [
                'original' => $title, 
                'corrected' => $improvedTitle
            ]);
            $title = $improvedTitle;
        }

        Log::info('Extracted reminder details:', [
            'title' => $title, 
            'time' => $time,
            'future_day' => $futureDay ? $futureDay->format('Y-m-d') : null
        ]);

        // Create the reminder with all required fields
        $startDate = $futureDay ?? $this->getUserTime();
        
        // If the time has already passed today and no future day was specified,
        // automatically schedule it for tomorrow
        $reminderTime = Carbon::parse($time);
        $currentTime = $this->getUserTime();
        if (!$futureDay && 
            ($reminderTime->hour < $currentTime->hour || 
            ($reminderTime->hour == $currentTime->hour && $reminderTime->minute <= $currentTime->minute))) {
            Log::info('Time has already passed today, scheduling for tomorrow', [
                'current_time' => $currentTime->format('H:i'),
                'reminder_time' => $reminderTime->format('H:i')
            ]);
            $startDate = $this->getUserTime()->addDay();
        }
        
        $reminder = Reminder::create([
            'title' => $title,
            'description' => 'Creat prin comandă vocală',
            'start_date' => $startDate,
            'frequency' => 'daily',
            'priority' => 0,
            'created_by' => $this->user->id,
            'status' => 'active',
            'next_occurrence' => $startDate->copy()->setTimeFromTimeString($time), // Use copy() to avoid modifying $startDate
            'end_date' => $startDate->copy()->endOfDay(), // Set to end of the same day
            'category' => 'general'
        ]);

        // Attach the user to the reminder
        $reminder->users()->attach($this->user->id);

        Log::info('Reminder created successfully:', ['reminder_id' => $reminder->id]);
        
        // Format the response based on whether it's for today or a future day
        $dayText = $futureDay ? ' pentru ' . $futureDay->format('d.m.Y') : '';
        
        // If we corrected the medication name, include both versions in the response
        if ($improvedTitle !== $originalTitle) {
            $this->responseText = "Am creat memento-ul: {$title} la ora {$time}{$dayText}. Am corectat numele medicamentului din '{$originalTitle}' în '{$title}'.";
        } else {
            $this->responseText = "Am creat memento-ul: {$title} la ora {$time}{$dayText}.";
        }
    }

    /**
     * Check if the title might be a misspelled medication name and fix it
     * 
     * @param string $title The original title
     * @return string The corrected title or the original if no correction needed
     */
    protected function checkAndFixMedicationName(string $title)
    {
        try {
            // Skip if OpenAI API key is not configured or title is too short
            if (!config('services.openai.api_key') || strlen($title) < 3) {
                return $title;
            }
            
            $systemPrompt = 'You are a medication name matching system for elderly users in Romania. 
            Your task is to determine if the user\'s input is a misspelled or mispronounced name of a common Romanian medication.
            If it is, provide the correct medication name. Only respond with a corrected name if you are very confident.
            If you are not confident, return the original name unchanged.
            
            Common Romanian medications include:
            Paracetamol, Algocalmin, Nurofen, Aspirin, Ibuprofen, Ketonal, Fasconal, Piafen,
            Metamizol, Colebil, Fervex, Strepsils, Theraflu, Decasept, Coldrex, Tantum Verde,
            Claritine, Zyrtec, Aerius, Vibrocil, Bixtonim, Olynth, Otipax, Oticalm,
            Amoxicilina, Augmentin, Cefort, Cefuroxim, Ciprofloxacin, Gentamicina, Zentel,
            Miconal, Fluconazol, Canesten, Aciclovir, Zovirax, Oseltamivir, Tamiflu,
            Omeprazol, Controloc, Nexium, Pantoprazol, Esomeprazol, Ranitidina, Metoclopramid,
            Drotaverina, No-Spa, Dulcolax, Senna, Loperamid, Smecta, Linex, Debridat,
            Diazepam, Xanax, Alprazolam, Oxazepam, Levomepromazina, Haloperidol, Risperidona,
            Atorvastatina, Sortis, Lipitor, Rosuvastatina, Crestor, Simvastatina, Fenofibrat,
            Metoprolol, Concor, Bisoprolol, Betaloc, Nebivolol, Atenolol, Propranolol,
            Amlodipina, Norvasc, Verapamil, Diltiazem, Nifedipina, Lercanidipina,
            Enalapril, Prestarium, Perindopril, Accupro, Quinapril, Ramipril, Tritace,
            Furosemid, Lasix, Indapamida, Spironolactona, Verospiron, Torasemid,
            Metformin, Siofor, Glucophage, Amaryl, Glimepirid, Diamicron, Gliclazida,
            Levotiroxina, Euthyrox, L-Thyroxin, Thybon, Vidalta, Betaserc, Tanakan,
            Piracetam, Memotropil, Gingko biloba, Bilobil, Cavinton, Vinpocetina,
            Prednison, Medrol, Metilprednisolon, Dexametazona, Fastum, Diclofenac,
            Tramadol, Algocalmin, Piafen, Paduden, No-Spa, Hidrasec, Dulcolax, Siofor, 
            Aspacardin, Anavenol, Cardiasol, Cebrium, Serlift, Detralex, Doxium, Apinevrin, 
            Monopril, Lorista, Escitalopram, Sermion, Nitromint, Nitroglicerina, Isoptin, 
            Plavix, Fraxiparina.
            
            Pay special attention to:
            1. Properly correcting spaces - for example "Aspa Cardin" should become "Aspacardin"
            2. Fixing common phonetic misspellings - like "ana veran" to "Anavenol"
            3. Ensuring proper capitalization - most medications start with capital letters
            
            Your response should be ONLY the corrected medication name without any explanation, or the exact original text if no correction is needed.';
            
            $userPrompt = "The user said: \"$title\". If this seems to be a misspelled Romanian medication name, provide the correct name. Otherwise, return exactly: \"$title\"";
            
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 50
            ]);
            
            $correctedName = trim($response->choices[0]->message->content);
            
            // Check if the response is significantly different from the original
            similar_text(strtolower($title), strtolower($correctedName), $percent);
            
            // Special handling for medication names with spaces that should be combined
            $titleNoSpaces = str_replace(' ', '', strtolower($title));
            $correctedNoSpaces = str_replace(' ', '', strtolower($correctedName));
            similar_text($titleNoSpaces, $correctedNoSpaces, $percentNoSpaces);
            
            // Accept the correction if either the regular similarity is high enough
            // or the no-spaces similarity is very high (for cases like "Aspa Cardin" -> "Aspacardin")
            if (($percent < 60 && $percentNoSpaces < 80) || 
                $correctedName === $title || 
                strlen($correctedName) > strlen($title) * 2 || 
                strlen($correctedName) < 3) {
                return $title;
            }
            
            Log::info('Medication name correction:', [
                'original' => $title,
                'corrected' => $correctedName,
                'similarity_percent' => $percent,
                'similarity_no_spaces_percent' => $percentNoSpaces
            ]);
            
            return $correctedName;
            
        } catch (\Exception $e) {
            Log::error('Error checking medication name:', ['error' => $e->getMessage()]);
            return $title;
        }
    }

    protected function handleCompleteReminder(string $text)
    {
        Log::info('Processing complete reminder command:', ['text' => $text]);
        
        // Get all active reminders for the user
        $reminders = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->where('reminder_user.completed', false)
            ->get();

        if ($reminders->isEmpty()) {
            $this->responseText = 'Nu aveți memento-uri active.';
            return;
        }

        // Use ChatGPT to find the best matching reminder
        $matchedReminderId = $this->findReminderWithFuzzyMatching($text, $reminders->pluck('title')->toArray(), $reminders->pluck('id')->toArray());
        
        if (!$matchedReminderId) {
            $this->responseText = 'Nu am găsit memento-ul specificat.';
            return;
        }

        // Get the matched reminder
        $reminder = $reminders->firstWhere('id', $matchedReminderId);
        
        if (!$reminder) {
            $this->responseText = 'Nu am putut găsi memento-ul.';
            return;
        }

        // Update both the reminders table and the reminder_user pivot table
        DB::transaction(function () use ($reminder) {
            // Update the reminder_user pivot table
            $this->user->assignedReminders()->updateExistingPivot($reminder->id, [
                'completed' => true,
                'completed_at' => now()
            ]);

            // Update the main reminders table
            $reminder->update([
                'completed' => true,
                'completed_at' => now()
            ]);
        });

        $this->responseText = "Am marcat memento-ul '{$reminder->title}' ca finalizat.";
    }

    /**
     * Find a reminder by fuzzy matching the title using ChatGPT
     * 
     * @param string $spokenTitle The title spoken by the user
     * @param array $availableTitles Array of available reminder titles
     * @param array $reminderIds Array of corresponding reminder IDs
     * @return int|null The ID of the matched reminder, or null if no match
     */
    protected function findReminderWithFuzzyMatching(string $spokenTitle, array $availableTitles, array $reminderIds)
    {
        try {
            // Skip if OpenAI API key is not configured or no available titles
            if (!config('services.openai.api_key') || empty($availableTitles)) {
                return null;
            }
            
            // Create a mapping of titles to IDs
            $titleToIdMap = array_combine($availableTitles, $reminderIds);
            
            $systemPrompt = 'You are a reminder matching system for elderly users. 
            Your task is to determine if the user\'s spoken reminder title matches one of the available reminders.
            If there is a match, respond with ONLY the number of the matching reminder.
            If there is no match, respond with "No match found".
            Consider typos, mispronunciations, partial matches, and phonetic similarities.';
            
            $availableTitlesNumbered = array_map(function($index, $title) {
                return ($index + 1) . ". " . $title;
            }, array_keys($availableTitles), $availableTitles);
            
            $userPrompt = "The user said they completed: \"$spokenTitle\"\n\nAvailable reminders:\n" . 
                implode("\n", $availableTitlesNumbered) . 
                "\n\nRespond with ONLY the number of the matching reminder (1, 2, etc.), or \"No match found\" if none matches.";
            
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 10
            ]);
            
            $result = trim($response->choices[0]->message->content);
            
            // Extract just the number from the response
            if (preg_match('/^(\d+)/', $result, $matches)) {
                $matchNumber = (int)$matches[1];
                
                // Check if the match number is valid
                if ($matchNumber >= 1 && $matchNumber <= count($availableTitles)) {
                    $matchedTitle = $availableTitles[$matchNumber - 1];
                    $matchedId = $titleToIdMap[$matchedTitle];
                    
                    Log::info('Fuzzy match found:', [
                        'spoken' => $spokenTitle,
                        'matched' => $matchedTitle,
                        'reminder_id' => $matchedId
                    ]);
                    
                    return $matchedId;
                }
            }
            
            Log::info('No fuzzy match found:', [
                'spoken' => $spokenTitle,
                'available' => $availableTitles,
                'gpt_response' => $result
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error in fuzzy matching:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function handleEmergency()
    {
        // Get the user's caregivers
        $caregivers = $this->user->caregivers;
        
        if ($caregivers->isEmpty()) {
            $this->responseText = 'Nu aveți îngrijitori asociați. Vă rugăm să contactați un îngrijitor manual.';
            return;
        }

        // Create emergency notification for each caregiver
        foreach ($caregivers as $caregiver) {
            $caregiver->notify(new EmergencyNotification($this->user));
        }

        $this->responseText = 'Am trimis o notificare de urgență către îngrijitorii dvs. Vă rugăm să așteptați ajutorul.';
    }

    protected function handleHelp()
    {
        $this->responseText = 'Puteți să-mi spuneți: ' .
            '"Ce am de făcut" pentru a vedea memento-urile, ' .
            '"Reamintește-mi să..." pentru a crea un memento nou, ' .
            '"Am făcut..." pentru a marca un memento ca finalizat, ' .
            '"Am luat medicamentul X astăzi?" pentru a verifica dacă ați luat un medicament, ' .
            '"Am mâncat astăzi?" pentru a verifica dacă ați luat masa, ' .
            '"Am făcut plimbarea astăzi?" pentru a verifica dacă ați făcut plimbarea, ' .
            '"Am măsurat tensiunea astăzi?" pentru a verifica dacă ați măsurat tensiunea, ' .
            '"Am măsurat glicemia astăzi?" pentru a verifica dacă ați măsurat glicemia, ' .
            'sau "SOS" pentru ajutor de urgență.';
    }

    protected function convertSpeechToText(string $audioFile)
    {
        try {
            Log::info('Converting speech to text:', ['file' => $audioFile]);
            
            // Read the audio file to check if it's valid
            $audioContent = file_get_contents($audioFile);
            if (!$audioContent) {
                throw new \Exception('Invalid audio file');
            }

            // Use OpenAI's Whisper API for speech-to-text
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->audio()->transcribe([
                'file' => fopen($audioFile, 'r'),
                'model' => 'whisper-1',
                'language' => 'ro',
                'response_format' => 'text'
            ]);

            $text = trim($response->text);
            Log::info('Transcribed text:', ['text' => $text]);
            
            return $text;
            
        } catch (\Exception $e) {
            Log::error('Speech to text conversion error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function generateResponseAudio()
    {
        try {
            // For now, we'll create a mock audio file
            // TODO: Implement actual text-to-speech conversion
            Log::info('Generating response audio');
            
            $filename = 'response_' . time() . '.mp3';
            $path = 'voice-responses/' . $filename;
            
            // Create a mock audio file with some content
            $mockAudioContent = str_repeat('0', 1000); // Create a 1KB mock audio file
            Storage::disk('public')->put($path, $mockAudioContent);
            
            $this->responseAudio = Storage::url($path);
            
            // TODO: Implement actual text-to-speech
            /*
            $client = new \Google\Cloud\TextToSpeech\V1\TextToSpeechClient([
                'credentials' => storage_path('app/google-cloud-credentials.json')
            ]);

            $input = new SynthesisInput([
                'text' => $this->responseText
            ]);

            $voice = new VoiceSelectionParams([
                'language_code' => 'ro-RO',
                'name' => 'ro-RO-Standard-A'
            ]);

            $audioConfig = new AudioConfig([
                'audio_encoding' => AudioEncoding::MP3
            ]);

            $response = $client->synthesizeSpeech($input, $voice, $audioConfig);
            $client->close();

            Storage::disk('public')->put($path, $response->getAudioContent());
            */
        } catch (\Exception $e) {
            Log::error('Text to speech conversion error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get the current time adjusted for user's timezone
     * This uses the timezone offset cookie set in the user's browser
     *
     * @return \Carbon\Carbon
     */
    protected function getUserTime()
    {
        $timezoneOffset = request()->cookie('timezone_offset');
        if ($timezoneOffset !== null) {
            // Cookie stores offset in minutes, apply it to the current time
            return now()->addMinutes($timezoneOffset);
        }
        
        return now();
    }

    protected function handleMedicationTaken($medicationName)
    {
        // Create a new activity record
        $activity = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'medication',
            'description' => $medicationName,
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activity->save();

        $this->responseText = "Am înregistrat că ați luat medicamentul $medicationName.";
    }

    protected function handleActivityCompleted($activity)
    {
        // Create a new activity record
        $activityRecord = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'activity',
            'description' => $activity,
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activityRecord->save();

        $this->responseText = "Am înregistrat că ați finalizat activitatea: $activity.";
    }

    protected function handleMealCompleted()
    {
        // Create a new activity record
        $activity = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'meal',
            'description' => 'A luat masa',
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activity->save();

        $this->responseText = 'Am înregistrat că ați luat masa.';
    }

    protected function handleWalkCompleted()
    {
        // Create a new activity record
        $activity = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'walk',
            'description' => 'A făcut plimbarea zilnică',
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activity->save();

        $this->responseText = 'Am înregistrat că ați făcut plimbarea zilnică.';
    }

    protected function handleBloodPressureMeasured()
    {
        // Create a new activity record
        $activity = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'blood_pressure',
            'description' => 'A măsurat tensiunea arterială',
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activity->save();

        $this->responseText = 'Am înregistrat măsurarea tensiunii arteriale.';
    }

    protected function handleBloodSugarMeasured()
    {
        // Create a new activity record
        $activity = new DailyActivity([
            'user_id' => $this->user->id,
            'type' => 'blood_sugar',
            'description' => 'A măsurat glicemia',
            'completed' => true,
            'completed_at' => now(),
            'date' => today()
        ]);
        $activity->save();

        $this->responseText = 'Am înregistrat măsurarea glicemiei.';
    }

    protected function handleActivityQuery($activity)
    {
        $activityType = $this->determineActivityType($activity);
        
        if (!$activityType) {
            $this->responseText = 'Nu am putut înțelege despre ce activitate întrebați.';
            return;
        }

        $activityRecord = DailyActivity::where('user_id', $this->user->id)
            ->whereDate('date', today())
            ->where('type', $activityType)
            ->where('completed', true)
            ->first();

        // Remove "am " from the activity text for the response
        $activityText = preg_replace('/^am /', '', $activity);

        if ($activityRecord) {
            $this->responseText = "Da, ați {$activityText} astăzi.";
        } else {
            $this->responseText = "Nu, nu ați {$activityText} astăzi.";
        }
    }

    protected function determineActivityType($text)
    {
        try {
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a Romanian language activity type classifier. 
                    Your task is to determine which type of activity the user is asking about.
                    Possible activity types are: walk, meal, blood_pressure, blood_sugar.
                    Respond with ONLY the activity type or "unknown" if you cannot determine it.'],
                    ['role' => 'user', 'content' => $text]
                ],
                'temperature' => 0.3,
                'max_tokens' => 10
            ]);

            $activityType = trim($response->choices[0]->message->content);
            
            // Validate the activity type
            if (in_array($activityType, ['walk', 'meal', 'blood_pressure', 'blood_sugar'])) {
                return $activityType;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error determining activity type:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function handleReminderQuery($reminderName)
    {
        Log::info('Processing reminder query:', ['reminder_name' => $reminderName]);

        // Get all reminders for today (both completed and uncompleted)
        $reminder = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->where(function($query) use ($reminderName) {
                $query->whereRaw('LOWER(reminders.title) = ?', [strtolower($reminderName)])
                      ->orWhereRaw('LOWER(reminders.title) LIKE ?', ['%' . strtolower($reminderName) . '%']);
            })
            ->first();

        if (!$reminder) {
            $this->responseText = "Nu am găsit memento-ul {$reminderName}.";
            return;
        }

        // Check if the reminder was completed today
        $completedToday = $this->user->assignedReminders()
            ->where('reminders.id', $reminder->id)
            ->where('reminder_user.completed', true)
            ->whereDate('reminder_user.completed_at', today())
            ->exists();

        if ($completedToday) {
            $this->responseText = "Da, ați luat {$reminder->title} astăzi.";
        } else {
            $this->responseText = "Nu, nu ați luat {$reminder->title} astăzi.";
        }
    }
} 