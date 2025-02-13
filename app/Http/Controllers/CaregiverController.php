<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\User;
use App\Models\CaregiverPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CaregiverController extends Controller
{
    public function dashboard()
    {
        $patients = auth()->user()->patients;
        $reminders = auth()->user()->patientReminders;
        return view('caregiver.dashboard', compact('patients', 'reminders'));
    }

    public function createReminder(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'required|string',
            'priority' => 'required|integer|min:1|max:5'
        ]);

        $reminder = new Reminder([
            ...$validated,
            'created_by' => auth()->id(),
            'status' => 'active'
        ]);

        $reminder->next_occurrence = $reminder->calculateNextOccurrence();
        $reminder->save();

        // Load the relationships if needed
        $reminder->load(['user', 'creator']);

        return response()->json([
            'message' => 'Reminder created successfully',
            'reminder' => [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'description' => $reminder->description,
                'schedule' => $reminder->schedule,
                'next_occurrence' => $reminder->next_occurrence->format('F j, Y g:i A'),
                'priority' => $reminder->priority,
                'patient_name' => $reminder->user->name
            ]
        ]);
    }

    public function createPatient(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $patient = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user'
        ]);

        // Link patient to caregiver
        CaregiverPatient::create([
            'caregiver_id' => auth()->id(),
            'patient_id' => $patient->id
        ]);

        return redirect()->back()->with('success', "Patient account for {$patient->name} created successfully!");
    }

    public function updateReminder(Request $request, Reminder $reminder)
    {
        // Add reminder update logic here
    }

    public function reminders()
    {
        $patients = auth()->user()->patients()->with('assignedReminders')->get();
        return view('caregiver.reminders', compact('patients'));
    }

    public function deleteReminder(Reminder $reminder)
    {
        // Verify the reminder belongs to a patient managed by this caregiver
        $isAuthorized = auth()->user()->patients()
            ->whereHas('assignedReminders', function($query) use ($reminder) {
                $query->where('id', $reminder->id);
            })->exists();

        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reminder->delete();
        return response()->json(['success' => true]);
    }
}
