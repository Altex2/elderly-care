<?php

namespace App\Http\Controllers\Caregiver;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $patients = $user->patients;
        $upcomingReminders = Reminder::whereIn('patient_id', $patients->pluck('id'))
            ->where('status', 'active')
            ->where('next_occurrence', '>=', now())
            ->orderBy('next_occurrence')
            ->take(5)
            ->get();

        $missedReminders = Reminder::whereIn('patient_id', $patients->pluck('id'))
            ->where('status', 'active')
            ->where('is_completed', false)
            ->where('next_occurrence', '<', now()->subHour())
            ->orderBy('next_occurrence')
            ->get();

        return view('caregiver.dashboard', compact('patients', 'upcomingReminders', 'missedReminders'));
    }
} 