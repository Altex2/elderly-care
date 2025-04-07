<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'push_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Patients managed by this caregiver
    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caregiver_patient', 'caregiver_id', 'patient_id')
                    ->whereRaw('users.role = ?', ['user'])
                    ->withTimestamps();
    }

    // Caregivers managing this patient
    public function caregivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caregiver_patient', 'patient_id', 'caregiver_id')
                    ->where('role', 'caregiver')
                    ->withTimestamps();
    }

    // Get all reminders for a caregiver's patients
    public function patientReminders(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reminder::class,
            CaregiverPatient::class,
            'caregiver_id',
            'created_by',
            'id',
            'patient_id'
        )->select('reminders.*')
         ->join('reminder_user', function($join) {
             $join->on('reminders.id', '=', 'reminder_user.reminder_id')
                  ->where('reminder_user.user_id', '=', 'caregiver_patient.patient_id');
         })
         ->where('caregiver_patient.caregiver_id', $this->id)
         ->distinct();
    }

    // Get reminders created by this user
    public function createdReminders(): HasMany
    {
        return $this->hasMany(Reminder::class, 'created_by');
    }

    // Get reminders assigned to this user
    public function assignedReminders(): BelongsToMany
    {
        return $this->belongsToMany(Reminder::class, 'reminder_user')
            ->withPivot('completed', 'completed_at')
            ->withTimestamps()
            ->where('reminders.status', 'active');
    }

    // Get completed reminders for this user
    public function completedReminders(): BelongsToMany
    {
        return $this->belongsToMany(Reminder::class, 'reminder_user')
            ->withPivot('completed', 'completed_at')
            ->withTimestamps()
            ->where('reminder_user.completed', true)
            ->orderBy('reminder_user.completed_at', 'desc');
    }

    public function isCaregiver(): bool
    {
        return $this->role === 'caregiver';
    }

    public function isPatient(): bool
    {
        return $this->role === 'user';
    }

    public function emergencyEvents(): HasMany
    {
        return $this->hasMany(EmergencyEvent::class);
    }

    public function healthLogs()
    {
        return $this->hasMany(HealthLog::class);
    }

    public function smartHomeDevices()
    {
        return $this->hasMany(SmartHomeDevice::class);
    }

    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }
}
