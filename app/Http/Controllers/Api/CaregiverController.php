<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Reminder;
use Illuminate\Http\Request;

class CaregiverController extends Controller
{
    public function getPatients()
    {
        $caregiver = auth()->user();
        $patients = $caregiver->patients()->with('assignedReminders')->get();
        
        return response()->json([
            'patients' => $patients
        ]);
    }

    public function getReminders()
    {
        $caregiver = auth()->user();
        $reminders = $caregiver->patientReminders()
            ->with('user')
            ->get();

        return response()->json([
            'reminders' => $reminders
        ]);
    }

    public function createPatient(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $patient = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'user'
        ]);

        auth()->user()->patients()->attach($patient->id);

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $patient
        ]);
    }

    public function createReminder(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly,once',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'priority' => 'required|integer|min:1|max:5',
            'user_id' => 'required|exists:users,id'
        ]);

        $caregiver = auth()->user();
        
        // Simplified query to check if the patient belongs to the caregiver
        $isAuthorized = $caregiver->patients()
            ->where('users.id', $request->user_id)
            ->exists();

        if (!$isAuthorized) {
            return response()->json([
                'message' => 'Unauthorized access to this patient'
            ], 403);
        }

        $reminder = Reminder::create([
            'title' => $request->title,
            'description' => $request->description,
            'frequency' => $request->frequency,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'priority' => $request->priority,
            'created_by' => $caregiver->id,
            'status' => 'active',
            'next_occurrence' => now()
        ]);

        // Assign the reminder to the patient
        $reminder->users()->attach($request->user_id);

        // Calculate the next occurrence based on the frequency
        $reminder->next_occurrence = $reminder->calculateNextOccurrence();
        $reminder->save();

        return response()->json([
            'message' => 'Reminder created successfully',
            'reminder' => $reminder
        ]);
    }

    public function updateReminder(Request $request, Reminder $reminder)
    {
        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'string',
            'priority' => 'integer|min:1|max:5',
            'status' => 'string|in:active,completed,cancelled'
        ]);

        $reminder->update($request->all());

        return response()->json([
            'message' => 'Reminder updated successfully',
            'reminder' => $reminder
        ]);
    }

    public function deleteReminder(Reminder $reminder)
    {
        $reminder->delete();

        return response()->json([
            'message' => 'Reminder deleted successfully'
        ]);
    }

    public function removePatient(User $patient)
    {
        auth()->user()->patients()->detach($patient->id);

        return response()->json([
            'message' => 'Patient removed successfully'
        ]);
    }

    public function updatePatient(Request $request, User $patient)
    {
        $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $patient->id,
        ]);

        $patient->update($request->only(['name', 'email']));

        return response()->json([
            'message' => 'Patient updated successfully',
            'patient' => $patient
        ]);
    }
} 