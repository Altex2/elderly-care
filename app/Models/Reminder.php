<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'description',
        'schedule',
        'priority',
        'status',
        'next_occurrence',
        'completed',
        'completed_at'
    ];

    protected $casts = [
        'next_occurrence' => 'datetime',
        'priority' => 'integer',
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(ReminderLog::class);
    }

    // Helper method to calculate next occurrence based on schedule
    public function calculateNextOccurrence()
    {
        // This is a basic implementation - you might want to make it more sophisticated
        $schedule = strtolower($this->schedule);
        $now = now();

        if (str_contains($schedule, 'daily')) {
            // If time is specified like "daily at 9am"
            if (preg_match('/daily at (\d{1,2})(?::\d{2})?\s*(am|pm)?/i', $schedule, $matches)) {
                $hour = intval($matches[1]);
                $meridiem = $matches[2] ?? '';

                // Convert to 24-hour format if needed
                if (strtolower($meridiem) === 'pm' && $hour < 12) {
                    $hour += 12;
                } elseif (strtolower($meridiem) === 'am' && $hour === 12) {
                    $hour = 0;
                }

                $next = $now->copy()->setHour($hour)->setMinute(0)->setSecond(0);
                if ($next->isPast()) {
                    $next->addDay();
                }
                return $next;
            }
            // Simple daily
            return $now->addDay()->startOfDay();
        }

        if (str_contains($schedule, 'weekly')) {
            return $now->addWeek()->startOfDay();
        }

        if (str_contains($schedule, 'monthly')) {
            return $now->addMonth()->startOfDay();
        }

        // Default to tomorrow if schedule format is not recognized
        return $now->addDay()->startOfDay();
    }
}
