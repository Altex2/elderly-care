<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class EmergencyCallService
{
    private $twilioClient;
    private $fromNumber;

    public function __construct()
    {
        $this->twilioClient = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->fromNumber = config('services.twilio.from_number');
    }

    public function initiateCall(User $user, string $contactName)
    {
        try {
            // Get the contact's phone number from the user's emergency contacts
            $contact = $user->emergencyContacts()
                ->where('name', 'like', "%{$contactName}%")
                ->first();

            if (!$contact) {
                throw new \Exception("Contact not found: {$contactName}");
            }

            // Create a Twilio call
            $call = $this->twilioClient->calls->create(
                $contact->phone_number,
                $this->fromNumber,
                [
                    'url' => route('emergency.voice'),
                    'statusCallback' => route('emergency.status-callback'),
                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                    'statusCallbackMethod' => 'POST'
                ]
            );

            Log::info('Emergency call initiated:', [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'call_sid' => $call->sid
            ]);

            return [
                'success' => true,
                'message' => "Se inițiază apelul către {$contact->name}...",
                'call_sid' => $call->sid
            ];
        } catch (\Exception $e) {
            Log::error('Emergency call failed:', [
                'user_id' => $user->id,
                'contact_name' => $contactName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'A apărut o eroare la inițierea apelului de urgență.'
            ];
        }
    }

    public function handleCallStatus(string $callSid, string $status)
    {
        Log::info('Emergency call status update:', [
            'call_sid' => $callSid,
            'status' => $status
        ]);

        // TODO: Implement call status handling logic
        // For example, updating the emergency event status
        // or notifying caregivers about the call status
    }
} 