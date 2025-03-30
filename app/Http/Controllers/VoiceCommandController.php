<?php

namespace App\Http\Controllers;

use App\Services\VoiceAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoiceCommandController extends Controller
{
    protected $voiceAgentService;

    public function __construct(VoiceAgentService $voiceAgentService)
    {
        $this->voiceAgentService = $voiceAgentService;
    }

    public function processCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'timezone_offset' => 'required|integer'
        ]);

        $user = Auth::user();
        
        if (!$user || $user->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $result = $this->voiceAgentService->processVoiceCommand(
            $request->command,
            $request->timezone_offset,
            $user
        );

        return response()->json($result);
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