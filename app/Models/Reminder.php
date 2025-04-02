<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'is_forever',
        'time',
        'created_by',
        'status',
        'completed',
        'completed_at',
        'next_occurrence'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_forever' => 'boolean',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'next_occurrence' => 'datetime'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reminder_user')
            ->withPivot('completed', 'completed_at')
            ->withTimestamps();
    }

    public function calculateNextOccurrence(): void
    {
        if ($this->is_forever) {
            $this->next_occurrence = Carbon::parse($this->start_date);
            return;
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        // If the start date is in the future, use it as the next occurrence
        if ($startDate->isFuture()) {
            $this->next_occurrence = $startDate;
            return;
        }

        // If we have an end date and it's in the past, don't set next occurrence
        if ($endDate && $endDate->isPast()) {
            $this->next_occurrence = null;
            return;
        }

        // Calculate the next occurrence based on frequency
        $next = $startDate->copy();
        $maxIterations = 1000; // Prevent infinite loops
        $iterations = 0;

        while ($next->isPast() && $iterations < $maxIterations) {
            $iterations++;
            switch ($this->frequency) {
                case 'daily':
                    $next->addDay();
                    break;
                case 'weekly':
                    $next->addWeek();
                    break;
                case 'monthly':
                    $next->addMonth();
                    break;
                case 'yearly':
                    $next->addYear();
                    break;
                case 'once':
                    $this->next_occurrence = null;
                    return;
            }
        }

        // If we hit the max iterations, set next occurrence to null
        if ($iterations >= $maxIterations) {
            $this->next_occurrence = null;
            return;
        }

        // If we have an end date and the calculated next occurrence is after it,
        // don't set next occurrence
        if ($endDate && $next->gt($endDate)) {
            $this->next_occurrence = null;
            return;
        }

        $this->next_occurrence = $next;
    }

    public function isActive(): bool
    {
        if ($this->is_forever) {
            return true;
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        if ($endDate && $endDate->isPast()) {
            return false;
        }

        return $startDate->isPast() || $startDate->isToday();
    }

    public function getStatusText(): string
    {
        if ($this->completed) {
            return 'Completat';
        }

        if (!$this->isActive()) {
            return 'Inactiv';
        }

        if ($this->is_forever) {
            return 'Activ (permanent)';
        }

        return 'Activ';
    }

    public function getFrequencyText(): string
    {
        return match($this->frequency) {
            'daily' => 'Zilnic',
            'weekly' => 'Săptămânal',
            'monthly' => 'Lunar',
            'yearly' => 'Anual',
            default => 'O singură dată'
        };
    }
}
