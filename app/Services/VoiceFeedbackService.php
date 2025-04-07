<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VoiceFeedbackService
{
    private const API_URL = 'https://api.elevenlabs.io/v1/text-to-speech';
    private const VOICE_ID = '21m00Tcm4TlvDq8ikWAM'; // Romanian voice ID
    private const API_KEY = null; // Should be set in .env

    public function generateResponse(string $text): string
    {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => config('services.elevenlabs.api_key'),
                'Content-Type' => 'application/json'
            ])->post(self::API_URL . '/' . self::VOICE_ID, [
                'text' => $text,
                'model_id' => 'eleven_monolingual_v1',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.75
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to generate voice response');
            }

            // Store the audio file
            $filename = 'voice_feedback_' . time() . '.mp3';
            Storage::disk('public')->put('voice/' . $filename, $response->body());

            return asset('storage/voice/' . $filename);
        } catch (\Exception $e) {
            Log::error('Voice feedback generation failed:', ['error' => $e->getMessage()]);
            return '';
        }
    }
} 