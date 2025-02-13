<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reminder;
use App\Services\VoiceService;
use App\Services\AIPersonalizationService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    protected $voiceService;
    protected $aiService;

    public function __construct(VoiceService $voiceService, AIPersonalizationService $aiService)
    {
        $this->voiceService = $voiceService;
        $this->aiService = $aiService;
    }

    public function index()
    {
        $users = User::all();
        $reminders = Reminder::with(['user'])->get();
        return view('test.dashboard', compact('users', 'reminders'));
    }

    public function testVoice(Request $request)
    {
        try {
            $audioFile = $request->file('audio');
            $transcription = $this->voiceService->transcribeAudio($audioFile);

            return response()->json([
                'success' => true,
                'transcription' => $transcription,
                'intent' => $this->aiService->analyzeIntent($transcription)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testNotification(Request $request)
    {
        // Test notification functionality
        return response()->json(['message' => 'Test notification sent']);
    }
}
