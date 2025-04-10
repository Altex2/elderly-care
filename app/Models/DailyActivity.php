<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DailyActivity extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'description',
        'completed',
        'completed_at',
        'date'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope for today's activities
    public function scopeToday(Builder $query)
    {
        return $query->whereDate('date', today());
    }

    // Scope for specific activity type
    public function scopeOfType(Builder $query, string $type)
    {
        return $query->where('type', $type);
    }

    // Scope for completed activities
    public function scopeCompleted(Builder $query)
    {
        return $query->where('completed', true);
    }

    // Scope for specific medication
    public function scopeMedication(Builder $query, string $medicationName)
    {
        return $query->where('type', 'medication')
                    ->where('description', $medicationName);
    }

    // Mark activity as completed
    public function markAsCompleted()
    {
        $this->update([
            'completed' => true,
            'completed_at' => now()
        ]);
    }
} 