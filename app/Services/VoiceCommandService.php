<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenAI\OpenAI;

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

        // Use OpenAI to improve command recognition
        $improvedText = $this->improveCommandRecognition($text);
        if ($improvedText !== $text) {
            Log::info('Improved command text:', ['original' => $text, 'improved' => $improvedText]);
            $text = strtolower(trim($improvedText)); // Make sure to use lowercase for consistent matching
        }

        // List reminders
        if (preg_match('/(ce am de făcut|ce am de facut|ce trebuie să fac|lista de memento|arată-mi memento)/', $text)) {
            Log::info('Command type: List reminders');
            $this->handleRemindersList();
            return;
        }

        // Create new reminder
        if (preg_match('/(memento nou|reamintește-mi|reaminteste-mi|reaminte-mi|reaminte mi)/', $text)) {
            Log::info('Command type: Create new reminder');
            $this->handleNewReminder($text);
            return;
        }

        // Complete reminder
        if (preg_match('/(am făcut|am facut|am terminat|am luat|am băut|am completat)/', $text)) {
            Log::info('Command type: Complete reminder');
            $this->handleCompleteReminder($text);
            return;
        }

        // Emergency
        if (preg_match('/(sos|urgență|urgenta|am nevoie de ajutor|ajutor)/', $text)) {
            Log::info('Command type: Emergency');
            $this->handleEmergency();
            return;
        }

        // Help
        if (preg_match('/(ce poți să faci|ce poti sa faci|ajută-mă|ajuta-ma|ajută mă)/', $text)) {
            Log::info('Command type: Help');
            $this->handleHelp();
            return;
        }

        // Unknown command
        Log::warning('Unknown command pattern:', ['text' => $text]);
        $this->responseText = 'Nu am înțeles comanda. Vă rugăm să încercați din nou.';
    }

    /**
     * Use OpenAI to improve command recognition by matching to known patterns
     * 
     * @param string $text The transcribed text
     * @return string The improved command text
     */
    protected function improveCommandRecognition(string $text)
    {
        try {
            // Skip if OpenAI API key is not configured
            if (!config('services.openai.api_key')) {
                Log::warning('OpenAI API key not configured. Skipping command recognition improvement.');
                return $text;
            }
            
            // Define known command patterns
            $knownPatterns = [
                // List reminders patterns
                'Ce am de făcut',
                'Ce am de făcut azi',
                'Arată-mi memento-urile',
                'Lista de memento-uri',
                
                // Create reminder patterns
                'Memento nou la ora {time} {title}',
                'Reamintește-mi să {action} la ora {time}',
                'Memento nou mâine la ora {time} {title}',
                'Reamintește-mi mâine să {action} la ora {time}',
                
                // Complete reminder patterns
                'Am făcut {reminder_name}',
                'Am terminat {reminder_name}',
                'Am luat {reminder_name}',
                'Am completat {reminder_name}',
                
                // Help and emergency patterns
                'Ce poți să faci',
                'Ajută-mă',
                'SOS',
                'Urgență'
            ];
            
            $systemPrompt = 'You are a voice command processor for an elderly care application. 
            Your task is to match the transcribed text to the closest known command pattern.
            You should only output the corrected command, nothing else.';
            
            $userPrompt = "The transcribed text is: \"$text\"\n\nKnown command patterns:\n- " . 
                implode("\n- ", $knownPatterns) . 
                "\n\nOutput only the corrected command that best matches, preserving any specific details like times, names, or actions.";
            
            $client = \OpenAI::client(config('services.openai.api_key'));
            
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 100
            ]);
            
            $improvedText = trim($response->choices[0]->message->content);
            
            // Validate that the improvement isn't just an explanation
            if (strlen($improvedText) > 200 || strpos($improvedText, 'closest match') !== false) {
                Log::warning('OpenAI provided an explanation instead of a corrected command', ['response' => $improvedText]);
                return $text;
            }
            
            return $improvedText;
        } catch (\Exception $e) {
            Log::error('Error improving command recognition:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return original text if there's an error
            return $text;
        }
    }

    protected function handleRemindersList()
    {
        $userTime = $this->getUserTime();
        
        $reminders = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->where('reminder_user.completed', false)
            ->orderBy('reminders.next_occurrence')
            ->get();

        if ($reminders->isEmpty()) {
            $this->responseText = 'Nu aveți memento-uri active pentru astăzi.';
            return;
        }

        $this->responseText = 'Iată memento-urile dvs. pentru astăzi: ';
        foreach ($reminders as $reminder) {
            $this->responseText .= $reminder->title . ' la ora ' . 
                Carbon::parse($reminder->next_occurrence)->format('H:i') . '. ';
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
        $this->responseText = "Am creat memento-ul: {$title} la ora {$time}{$dayText}.";
    }

    protected function handleCompleteReminder(string $text)
    {
        Log::info('Processing complete reminder command:', ['text' => $text]);
        
        // Extract the reminder title from various patterns
        $patterns = [
            '/am (făcut|terminat|luat|completat) (.+)/i',
            '/am (luat|băut) (medicamentul|pastila|tableta) (.+)/i',
            '/am luat (.+)/i'
        ];

        $matches = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                Log::info('Pattern matched:', ['pattern' => $pattern, 'matches' => $matches]);
                break;
            }
        }

        if (!$matches || count($matches) < 3) {
            Log::warning('Failed to match completion pattern:', ['text' => $text]);
            $this->responseText = 'Nu am putut înțelege ce memento doriți să marcați ca finalizat.';
            return;
        }

        // Get the title from the last match group
        $title = trim($matches[count($matches) - 1]);
        // Clean up the title by removing punctuation and extra spaces
        $title = preg_replace('/[.,!?;:]/', '', $title);
        $searchTitle = strtolower(trim($title));

        Log::info('Looking for reminder:', [
            'search_title' => $searchTitle,
            'original_title' => $title
        ]);

        // Get all active reminders for debugging
        $allReminders = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->where('reminder_user.completed', false)
            ->get();

        Log::info('Available reminders:', [
            'count' => $allReminders->count(),
            'titles' => $allReminders->pluck('title')->toArray()
        ]);

        // Find the reminder using case-insensitive search
        $reminder = $this->user->assignedReminders()
            ->where('reminders.status', 'active')
            ->where('reminder_user.completed', false)
            ->where(function($query) use ($searchTitle) {
                $query->whereRaw('LOWER(reminders.title) = ?', [$searchTitle])
                      ->orWhereRaw('LOWER(reminders.title) LIKE ?', ['%' . $searchTitle . '%']);
            })
            ->first();

        if (!$reminder) {
            Log::warning('Reminder not found:', [
                'search_title' => $searchTitle,
                'available_reminders' => $allReminders->pluck('title')->toArray()
            ]);
            $this->responseText = "Nu am găsit memento-ul: {$title}.";
            return;
        }

        Log::info('Found reminder:', [
            'reminder_id' => $reminder->id,
            'title' => $reminder->title,
            'matched_title' => $title
        ]);

        // Update the pivot table
        $this->user->assignedReminders()->updateExistingPivot($reminder->id, [
            'completed' => true,
            'completed_at' => $this->getUserTime()
        ]);

        $this->responseText = "Am marcat memento-ul {$reminder->title} ca finalizat.";
    }

    protected function handleEmergency()
    {
        $this->responseText = 'Vă ajut imediat! Contactez serviciile de urgență...';
        // TODO: Implement emergency contact logic
    }

    protected function handleHelp()
    {
        $this->responseText = 'Puteți să-mi spuneți: ' .
            '"Ce am de făcut" pentru a vedea memento-urile, ' .
            '"Reamintește-mi să..." pentru a crea un memento nou, ' .
            '"Am făcut..." pentru a marca un memento ca finalizat, ' .
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
} 