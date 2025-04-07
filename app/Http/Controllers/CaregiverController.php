<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\User;
use App\Models\CaregiverPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly,once',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'priority' => 'required|integer|min:1|max:5'
        ]);

        // Verify that the user is a patient managed by this caregiver
        $isAuthorized = auth()->user()->patients()
            ->where('users.id', $validated['user_id'])
            ->exists();

        if (!$isAuthorized) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only create reminders for your patients.'
            ], 403);
        }

        try {
            // Create the reminder
            $reminder = new Reminder([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'frequency' => $validated['frequency'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'priority' => $validated['priority'],
                'created_by' => auth()->id(),
                'status' => 'active',
                'is_forever' => false,
                'completed' => false
            ]);

            // Calculate next occurrence
            $reminder->next_occurrence = $reminder->calculateNextOccurrence();
            $reminder->save();

            // Link the reminder to the patient
            $reminder->assignedUsers()->attach($validated['user_id'], [
                'completed' => false,
                'completed_at' => null
            ]);

            // Load the relationships
            $reminder->load(['assignedUsers', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Memento creat cu succes',
                'reminder' => [
                    'id' => $reminder->id,
                    'title' => $reminder->title,
                    'description' => $reminder->description,
                    'frequency' => $reminder->getFrequencyText(),
                    'next_occurrence' => $reminder->next_occurrence->format('d.m.Y H:i'),
                    'priority' => $reminder->priority,
                    'patient_name' => $reminder->assignedUsers->first()->name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Reminder creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare la crearea memento-ului: ' . $e->getMessage()
            ], 500);
        }
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
                $query->where('reminders.id', $reminder->id);
            })->exists();

        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reminder->delete();
        return response()->json(['success' => true]);
    }
}
