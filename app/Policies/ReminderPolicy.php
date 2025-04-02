<?php

namespace App\Policies;

use App\Models\Reminder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReminderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Reminder $reminder): bool
    {
        // Users can view reminders they created or are assigned to
        return $user->id === $reminder->created_by || 
               $reminder->assignedUsers()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Reminder $reminder): bool
    {
        // Only the creator can update the reminder
        return $user->id === $reminder->created_by;
    }

    public function delete(User $user, Reminder $reminder): bool
    {
        // Only the creator can delete the reminder
        return $user->id === $reminder->created_by;
    }

    public function complete(User $user, Reminder $reminder): bool
    {
        // Users can complete reminders they are assigned to
        return $reminder->assignedUsers()->where('user_id', $user->id)->exists();
    }
} 