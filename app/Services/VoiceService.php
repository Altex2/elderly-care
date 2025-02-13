<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Storage;

class VoiceService
{
    public function transcribeAudio($audioFile)
    {
        $response = OpenAI::audio()->transcribe([
            'model' => 'whisper-1',
            'file' => $audioFile,
            'response_format' => 'text'
        ]);

        return $response->text;
    }

    public function synthesizeSpeech($text)
    {
        // Using ElevenLabs API for text-to-speech
        $apiKey = config('services.elevenlabs.key');
        $voiceId = config('services.elevenlabs.voice_id');

        $client = new \GuzzleHttp\Client();

        $response = $client->post("https://api.elevenlabs.io/v1/text-to-speech/$voiceId", [
            'headers' => [
                'xi-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'text' => $text,
                'model_id' => 'eleven_monolingual_v1',
            ],
        ]);

        return $response->getBody();
    }
}
