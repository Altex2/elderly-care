<?php

namespace App\Http\Controllers;

use App\Services\EmergencyCallService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class EmergencyController extends Controller
{
    protected $emergencyCallService;

    public function __construct(EmergencyCallService $emergencyCallService)
    {
        $this->emergencyCallService = $emergencyCallService;
    }

    public function contacts()
    {
        $contacts = auth()->user()->emergencyContacts()->orderBy('priority', 'desc')->get();
        return view('emergency.contacts', compact('contacts'));
    }

    public function initiateCall(Request $request)
    {
        $request->validate([
            'contact_name' => 'required|string'
        ]);

        $result = $this->emergencyCallService->initiateCall(
            auth()->user(),
            $request->contact_name
        );

        return response()->json($result);
    }

    public function voice()
    {
        $response = new VoiceResponse();
        
        // Add a pause to allow the call to connect
        $response->pause(['length' => 1]);
        
        // Play the emergency message
        $response->say(
            'Bună ziua! Acesta este un apel de urgență de la sistemul de îngrijire a vârstnicilor. ' .
            'Vă rugăm să răspundeți la acest apel cât mai curând posibil.',
            ['language' => 'ro-RO']
        );
        
        // Add another pause
        $response->pause(['length' => 1]);
        
        // Repeat the message
        $response->say(
            'Vă rugăm să răspundeți la acest apel de urgență.',
            ['language' => 'ro-RO']
        );
        
        return response($response);
    }

    public function statusCallback(Request $request)
    {
        $request->validate([
            'CallSid' => 'required|string',
            'CallStatus' => 'required|string'
        ]);

        $this->emergencyCallService->handleCallStatus(
            $request->CallSid,
            $request->CallStatus
        );

        return response()->json(['success' => true]);
    }
} 