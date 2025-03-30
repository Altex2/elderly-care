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
            ->where('status', 'active')
            ->where('completed', false)
            ->where('next_occurrence', '<', $userNow)
            ->orderBy('next_occurrence')
            ->get();

        $activeReminders = auth()->user()->assignedReminders()
            ->where('status', 'active')
            ->where('completed', false)
            ->where('next_occurrence', '>=', $userNow)
            ->orderBy('next_occurrence')
            ->get();

        $completedReminders = auth()->user()->assignedReminders()
            ->where('completed', true)
            ->orderBy('completed_at', 'desc')
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
