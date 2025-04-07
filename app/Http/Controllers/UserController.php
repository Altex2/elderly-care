<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserController extends Controller
{
    public function dashboard()
    {
        $timezoneOffset = request()->cookie('timezone_offset', 0);
        $userNow = now()->addMinutes($timezoneOffset);

        $overdueReminders = auth()->user()->assignedReminders()
            ->where('reminder_user.completed', false)
            ->where('reminders.next_occurrence', '<', $userNow)
            ->orderBy('reminders.next_occurrence')
            ->get();

        $activeReminders = auth()->user()->assignedReminders()
            ->where('reminder_user.completed', false)
            ->where('reminders.next_occurrence', '>=', $userNow)
            ->orderBy('reminders.next_occurrence')
            ->get();

        $completedReminders = auth()->user()->completedReminders()
            ->take(10)
            ->get();

        return view('user.dashboard', compact(
            'overdueReminders',
            'activeReminders',
            'completedReminders',
            'timezoneOffset'
        ));
    }
}
