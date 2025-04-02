<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $reminders = $user->is_caregiver
            ? Reminder::where('created_by', $user->id)->orderBy('next_occurrence')->get()
            : $user->assignedReminders()->orderBy('next_occurrence')->get();

        return view('reminders.index', compact('reminders'));
    }

    public function create()
    {
        return view('reminders.form');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'frequency' => 'required|string|in:daily,weekly,monthly,yearly,once',
                'time' => 'required|date_format:H:i',
                'start_date' => 'required|date',
                'duration_type' => 'required|in:forever,until',
                'end_date' => 'required_if:duration_type,until|nullable|date|after_or_equal:start_date',
            ]);

            $user = auth()->user();
            
            // Create the reminder
            $reminder = new Reminder();
            $reminder->title = $validated['title'];
            $reminder->description = $validated['description'];
            $reminder->frequency = $validated['frequency'];
            $reminder->time = $validated['time'];
            $reminder->start_date = Carbon::parse($validated['start_date'] . ' ' . $validated['time']);
            $reminder->is_forever = $validated['duration_type'] === 'forever';
            $reminder->end_date = $validated['duration_type'] === 'until' ? Carbon::parse($validated['end_date'] . ' ' . $validated['time']) : null;
            $reminder->created_by = $user->id;
            
            // Calculate next occurrence
            $reminder->calculateNextOccurrence();
            
            // Save the reminder
            $reminder->save();

            // Assign the reminder to users
            if ($user->isCaregiver()) {
                // If the creator is a caregiver, assign to their patients
                $patients = $user->patients()->get();
                $reminder->assignedUsers()->attach($patients->pluck('id'));
            } else {
                // If the creator is a patient, assign to themselves
                $reminder->assignedUsers()->attach($user->id);
            }

            return redirect()->route('reminders.index')
                ->with('success', 'Memento creat cu succes!');
        } catch (\Exception $e) {
            \Log::error('Error creating reminder: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'A apărut o eroare la crearea mementoului. Vă rugăm să încercați din nou.');
        }
    }

    public function edit(Reminder $reminder)
    {
        $this->authorize('update', $reminder);
        return view('reminders.form', compact('reminder'));
    }

    public function update(Request $request, Reminder $reminder)
    {
        $this->authorize('update', $reminder);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly,once',
            'time' => 'required|date_format:H:i',
            'start_date' => 'required|date',
            'duration_type' => 'required|in:forever,until',
            'end_date' => 'required_if:duration_type,until|nullable|date|after_or_equal:start_date',
        ]);

        $reminder->title = $validated['title'];
        $reminder->description = $validated['description'];
        $reminder->frequency = $validated['frequency'];
        $reminder->time = $validated['time'];
        $reminder->start_date = Carbon::parse($validated['start_date'] . ' ' . $validated['time']);
        $reminder->is_forever = $validated['duration_type'] === 'forever';
        $reminder->end_date = $validated['duration_type'] === 'until' ? Carbon::parse($validated['end_date'] . ' ' . $validated['time']) : null;
        
        // Recalculate next occurrence
        $reminder->calculateNextOccurrence();
        
        $reminder->save();

        return redirect()->route('reminders.index')
            ->with('success', 'Memento actualizat cu succes!');
    }

    public function destroy(Reminder $reminder)
    {
        $this->authorize('delete', $reminder);
        
        $reminder->delete();

        return redirect()->route('reminders.index')
            ->with('success', 'Memento șters cu succes!');
    }

    public function complete(Reminder $reminder)
    {
        $user = auth()->user();
        
        // Update the pivot table for the specific user
        $reminder->assignedUsers()->updateExistingPivot($user->id, [
            'completed' => true,
            'completed_at' => now()
        ]);

        // Check if all assigned users have completed the reminder
        $allCompleted = $reminder->assignedUsers()
            ->wherePivot('completed', false)
            ->doesntExist();

        if ($allCompleted) {
            $reminder->completed = true;
            $reminder->completed_at = now();
            $reminder->save();
        }

        return redirect()->back()
            ->with('success', 'Memento marcat ca fiind completat!');
    }
} 