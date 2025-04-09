<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\User;
use App\Models\CaregiverPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\JsonResponse;

class CaregiverController extends Controller
{
    public function dashboard()
    {
        /** @var User $user */
        $user = auth()->user();
        
        // Get all patients for this caregiver
        $patients = $user->patients()->get();
        
        // Get all active reminders for these patients
        $activeReminders = collect();
        foreach ($patients as $patient) {
            /** @var User $patient */
            $patientReminders = $patient->assignedReminders()
                ->where('status', 'active')
                ->where('reminder_user.completed', false)
                ->get();
            $activeReminders = $activeReminders->merge($patientReminders);
        }
        
        // Get today's reminders
        $todayReminders = $activeReminders->filter(function($reminder) {
            /** @var Reminder $reminder */
            return $reminder->next_occurrence->isToday() && !$reminder->next_occurrence->isPast();
        });
        
        // Get missed reminders (more than 1 hour past scheduled time)
        $missedReminders = $activeReminders->filter(function($reminder) {
            /** @var Reminder $reminder */
            return $reminder->isMissed();
        });
        
        // Get notifications
        $notifications = $user->notifications()->latest()->get();
        
        return view('caregiver.dashboard', compact(
            'patients',
            'activeReminders',
            'todayReminders',
            'missedReminders',
            'notifications'
        ));
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
                'completed' => false,
                'next_occurrence' => $validated['start_date']
            ]);

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
                    'next_occurrence' => Carbon::parse($reminder->next_occurrence)->format('d.m.Y H:i'),
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

    private function isAuthorizedForPatient($patientId): bool
    {
        /** @var User $user */
        $user = auth()->user();
        return $user->patients()
            ->where('users.id', $patientId)
            ->exists();
    }

    public function acceptMissedReminder(Request $request, Reminder $reminder): JsonResponse
    {
        // Validate request
        $request->validate([
            'patient_id' => 'required|exists:users,id',
        ]);

        // Check if caregiver is authorized
        if (!$this->isAuthorizedForPatient($request->patient_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Nu ai permisiunea să acționezi asupra acestui memento.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $now = now();
            
            // Calculate next occurrence based on frequency and completion time
            if ($reminder->frequency !== 'once') {
                $originalTime = Carbon::parse($reminder->start_date);
                
                switch ($reminder->frequency) {
                    case 'daily':
                        $nextDate = $now->copy()->addDay();
                        break;
                    case 'weekly':
                        $nextDate = $now->copy()->addWeek();
                        break;
                    case 'monthly':
                        $nextDate = $now->copy()->addMonth();
                        break;
                    case 'yearly':
                        $nextDate = $now->copy()->addYear();
                        break;
                    default:
                        $nextDate = null;
                }

                if ($nextDate) {
                    // Keep the original hour and minute
                    $nextDate->setTime($originalTime->hour, $originalTime->minute);
                    $reminder->next_occurrence = $nextDate;
                }
            }

            // Mark reminder as completed in the pivot table
            $reminder->users()->updateExistingPivot($request->patient_id, [
                'completed' => true,
                'completed_at' => $now,
                'completed_by' => auth()->id()
            ]);

            // Delete the notification
            /** @var User $user */
            $user = auth()->user();
            $user->notifications()
                ->where('type', 'App\Notifications\MissedReminderNotification')
                ->where('data->reminder_id', $reminder->id)
                ->delete();

            // Save the reminder with the new next_occurrence
            $reminder->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Memento marcat ca completat cu succes.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting missed reminder: ' . $e->getMessage(), [
                'reminder_id' => $reminder->id,
                'patient_id' => $request->patient_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare. Vă rugăm să încercați din nou.'
            ], 500);
        }
    }

    public function denyMissedReminder(Request $request, Reminder $reminder): JsonResponse
    {
        // Validate request
        $request->validate([
            'patient_id' => 'required|exists:users,id',
        ]);

        // Check if caregiver is authorized
        if (!$this->isAuthorizedForPatient($request->patient_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Nu ai permisiunea să acționezi asupra acestui memento.'
            ], 403);
        }

        try {
            // Mark reminder as skipped in the pivot table
            $reminder->users()->updateExistingPivot($request->patient_id, [
                'skipped' => true,
                'skip_reason' => 'Denied by caregiver'
            ]);

            // Delete the notification
            /** @var User $user */
            $user = auth()->user();
            $user->notifications()
                ->where('type', 'App\Notifications\MissedReminderNotification')
                ->where('data->reminder_id', $reminder->id)
                ->delete();

            // Calculate next occurrence
            if ($reminder->frequency !== 'once') {
                $reminder->next_occurrence = $reminder->calculateNextOccurrence();
                $reminder->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Memento reprogramat cu succes.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error denying missed reminder: ' . $e->getMessage(), [
                'reminder_id' => $reminder->id,
                'patient_id' => $request->patient_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'A apărut o eroare. Vă rugăm să încercați din nou.'
            ], 500);
        }
    }
}
