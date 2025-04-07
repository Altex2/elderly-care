<?php

namespace App\Http\Controllers;

use App\Services\VoiceCommandService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VoiceCommandController extends Controller
{
    protected $voiceCommandService;

    public function __construct(VoiceCommandService $voiceCommandService)
    {
        $this->voiceCommandService = $voiceCommandService;
    }

    public function index()
    {
        return view('voice.interface');
    }

    public function processCommand(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:webm,wav,mp3,ogg|max:10240'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Nu sunteți autentificat.'
            ], 401);
        }

        try {
            // Store the audio file temporarily
            $path = $request->file('audio')->store('temp/voice', 'public');
            
            // Create a new instance of the service with the authenticated user
            $voiceService = new VoiceCommandService($user);
            
            // Process the command
            $result = $voiceService->processCommand(storage_path('app/public/' . $path));
            
            // Clean up the temporary file
            Storage::disk('public')->delete($path);
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Voice command processing error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'A apărut o eroare la procesarea comenzii vocale.'
            ], 500);
        }
    }

    public function test()
    {
        try {
            // Test OpenAI API connection
            $client = \OpenAI::client(config('services.openai.api_key'));
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "Hello" in Romanian']
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OpenAI API connection successful!',
                'test_response' => $response->choices[0]->message->content,
                'api_key_status' => 'API key is configured'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error testing OpenAI API: ' . $e->getMessage(),
                'api_key_status' => 'API key configuration error'
            ], 500);
        }
    }
} 