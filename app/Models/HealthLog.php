<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'logged_at',
        'notes',
        'value',
        'unit'
    ];

    protected $casts = [
        'logged_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 