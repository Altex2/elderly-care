<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'next_occurrence',
        'priority',
        'type', // medication, appointment, task, etc.
        'medication_name',
        'medication_dosage',
        'medication_instructions',
        'escalation_level',
        'last_escalated_at',
        'voice_command',
        'confirmation_required',
        'confirmation_deadline',
        'repeat_until_confirmed',
        'category',
        'tags',
        'location',
        'attachments',
        'notes',
        'color',
        'is_recurring',
        'recurrence_rule'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_forever' => 'boolean',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'next_occurrence' => 'datetime',
        'confirmation_required' => 'boolean',
        'confirmation_deadline' => 'datetime',
        'repeat_until_confirmed' => 'boolean',
        'last_escalated_at' => 'datetime',
        'tags' => 'array',
        'attachments' => 'array',
        'notes' => 'array',
        'is_recurring' => 'boolean'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'reminder_user')
            ->withPivot('completed', 'completed_at')
            ->withTimestamps();
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'reminder_user')
            ->withPivot('completed', 'completed_at')
            ->withTimestamps();
    }

    public function calculateNextOccurrence(): ?Carbon
    {
        if ($this->is_forever) {
            return Carbon::parse($this->start_date);
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        // If we have an end date and it's in the past, don't set next occurrence
        if ($endDate && $endDate->isPast()) {
            return null;
        }

        // For new reminders (not completed yet), use the start date
        if (!$this->completed && !$this->completed_at) {
            return $startDate;
        }

        // If the reminder was completed, calculate next occurrence from completion time
        if ($this->completed && $this->completed_at) {
            $baseDate = Carbon::parse($this->completed_at);
            $originalTime = Carbon::parse($this->start_date);
            
            // Calculate the next occurrence based on frequency
            switch ($this->frequency) {
                case 'daily':
                    $next = $baseDate->copy()->addDay();
                    break;
                case 'weekly':
                    $next = $baseDate->copy()->addWeek();
                    break;
                case 'monthly':
                    $next = $baseDate->copy()->addMonth();
                    break;
                case 'yearly':
                    $next = $baseDate->copy()->addYear();
                    break;
                case 'once':
                    return null;
                default:
                    return null;
            }

            // Preserve the original time of day
            return $next->setTime($originalTime->hour, $originalTime->minute);
        }

        // If we get here, use the current next_occurrence
        return $this->next_occurrence ? Carbon::parse($this->next_occurrence) : $startDate;
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
        // Check if any user has completed this reminder
        $hasCompletedUsers = $this->users()
            ->wherePivot('completed', true)
            ->exists();

        if ($hasCompletedUsers) {
            return 'Completat';
        }

        if (!$this->isActive()) {
            return 'Inactiv';
        }

        if ($this->needsConfirmation()) {
            return 'Așteaptă confirmare';
        }

        if ($this->shouldEscalate()) {
            return 'Necesită atenție';
        }

        if ($this->is_forever) {
            return 'Activ (permanent)';
        }

        return 'Activ';
    }

    public function getStatusColor(): string
    {
        return match($this->getStatusText()) {
            'Completat' => 'green',
            'Inactiv' => 'gray',
            'Așteaptă confirmare' => 'yellow',
            'Necesită atenție' => 'red',
            default => 'blue'
        };
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

    public function confirmations(): HasMany
    {
        return $this->hasMany(ReminderConfirmation::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(ReminderEscalation::class);
    }

    public function voiceLogs(): HasMany
    {
        return $this->hasMany(VoiceLog::class);
    }

    public function needsConfirmation(): bool
    {
        if (!$this->confirmation_required) {
            return false;
        }

        if ($this->confirmation_deadline && $this->confirmation_deadline->isPast()) {
            return false;
        }

        return !$this->completed;
    }

    public function shouldEscalate(): bool
    {
        if (!$this->confirmation_required) {
            return false;
        }

        if (!$this->confirmation_deadline || !$this->confirmation_deadline->isPast()) {
            return false;
        }

        if ($this->last_escalated_at && $this->last_escalated_at->diffInHours(now()) < 24) {
            return false;
        }

        return true;
    }

    public function getEscalationLevel(): int
    {
        return $this->escalation_level ?? 1;
    }

    public function incrementEscalationLevel(): void
    {
        $this->escalation_level = ($this->escalation_level ?? 1) + 1;
        $this->last_escalated_at = now();
        $this->save();
    }

    public function getVoiceCommand(): ?string
    {
        return $this->voice_command;
    }

    public function getMedicationInfo(): ?array
    {
        if ($this->type !== 'medication') {
            return null;
        }

        return [
            'name' => $this->medication_name,
            'dosage' => $this->medication_dosage,
            'instructions' => $this->medication_instructions
        ];
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getAttachments(): array
    {
        return $this->attachments ?? [];
    }

    public function getNotes(): array
    {
        return $this->notes ?? [];
    }

    public function addNote(string $note, string $type = 'general'): void
    {
        $notes = $this->notes ?? [];
        $notes[] = [
            'content' => $note,
            'type' => $type,
            'created_at' => now()->toIso8601String()
        ];
        $this->notes = $notes;
        $this->save();
    }

    public function addAttachment(string $url, string $type = 'document'): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'url' => $url,
            'type' => $type,
            'added_at' => now()->toIso8601String()
        ];
        $this->attachments = $attachments;
        $this->save();
    }

    public function getTags(): array
    {
        return $this->tags ?? [];
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = $this->tags ?? [];
        $this->tags = array_diff($tags, [$tag]);
        $this->save();
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->save();
    }

    public function isMissed(): bool
    {
        if (!$this->next_occurrence || $this->completed) {
            return false;
        }

        return $this->next_occurrence->addHour()->isPast();
    }

    public function isTooLateToComplete(): bool
    {
        if ($this->completed_at) {
            return false;
        }

        $now = now();
        $nextOccurrence = Carbon::parse($this->next_occurrence);
        
        // Too late to complete if more than 2 hours have passed
        return $nextOccurrence->addHours(2)->isPast();
    }

    public function moveToNextOccurrence(): void
    {
        if (!$this->is_recurring) {
            return;
        }

        $nextOccurrence = Carbon::parse($this->next_occurrence);
        
        switch ($this->frequency) {
            case 'daily':
                $nextOccurrence->addDay();
                break;
            case 'weekly':
                $nextOccurrence->addWeek();
                break;
            case 'monthly':
                $nextOccurrence->addMonth();
                break;
        }

        $this->next_occurrence = $nextOccurrence;
        $this->save();
    }
}
