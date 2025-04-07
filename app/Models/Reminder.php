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

    public function calculateNextOccurrence(): ?Carbon
    {
        if ($this->is_forever) {
            return Carbon::parse($this->start_date);
        }

        $now = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;

        // If the start date is in the future, use it as the next occurrence
        if ($startDate->isFuture()) {
            return $startDate;
        }

        // If we have an end date and it's in the past, don't set next occurrence
        if ($endDate && $endDate->isPast()) {
            return null;
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
                    return null;
            }
        }

        // If we hit the max iterations, return null
        if ($iterations >= $maxIterations) {
            return null;
        }

        // If we have an end date and the calculated next occurrence is after it,
        // return null
        if ($endDate && $next->gt($endDate)) {
            return null;
        }

        return $next;
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
}
