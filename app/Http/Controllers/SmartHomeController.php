<?php

namespace App\Http\Controllers;

use App\Models\SmartHomeDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmartHomeController extends Controller
{
    public function index()
    {
        $devices = auth()->user()->smartHomeDevices;
        return response()->json($devices);
    }

    public function update(Request $request, SmartHomeDevice $device)
    {
        $request->validate([
            'state' => 'required|in:on,off,toggle',
            'settings' => 'nullable|array'
        ]);

        try {
            $device->update([
                'state' => $request->state,
                'settings' => $request->settings,
                'last_updated_at' => now()
            ]);

            // TODO: Implement actual device control logic
            $this->controlDevice($device);

            return response()->json([
                'success' => true,
                'message' => "Dispozitiv actualizat: {$device->name}",
                'device' => $device
            ]);
        } catch (\Exception $e) {
            Log::error('Smart home device control failed:', [
                'device_id' => $device->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la controlul dispozitivului.'
            ], 500);
        }
    }

    private function controlDevice(SmartHomeDevice $device)
    {
        // This is where you would implement the actual device control logic
        // For example, using MQTT, HTTP APIs, or other protocols
        // For now, we'll just log the action
        Log::info('Controlling smart home device:', [
            'device_id' => $device->id,
            'name' => $device->name,
            'type' => $device->type,
            'state' => $device->state,
            'settings' => $device->settings
        ]);
    }
} 