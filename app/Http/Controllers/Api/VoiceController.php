<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoiceService;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    protected $voiceService;

    public function __construct(VoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    public function processCommand(Request $request)
    {
        $command = $request->input('command');
        $timezoneOffset = $request->header('Timezone-Offset', 0);

        // Process the command using existing logic
        $response = $this->processVoiceCommand($command, $timezoneOffset);

        return response()->json([
            'success' => true,
            'message' => $response['message'],
            'action' => $response['action'] ?? null,
            'data' => $response['data'] ?? null
        ]);
    }

    public function textToSpeech(Request $request)
    {
        $text = $request->input('text');
        $audioResponse = $this->voiceService->synthesizeSpeech($text);

        return response($audioResponse)
            ->header('Content-Type', 'audio/mpeg')
            ->header('Accept-Ranges', 'bytes');
    }
} 