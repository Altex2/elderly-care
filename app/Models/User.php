<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
    public function patients()
    {
        return $this->belongsToMany(User::class, 'caregiver_patient', 'caregiver_id', 'patient_id')
                    ->where('role', 'user')
                    ->withTimestamps();
    }

    // Caregivers managing this patient
    public function caregivers()
    {
        return $this->belongsToMany(User::class, 'caregiver_patient', 'patient_id', 'caregiver_id')
                    ->where('role', 'caregiver')
                    ->withTimestamps();
    }

    // Get all reminders for a caregiver's patients
    public function patientReminders()
    {
        return $this->hasManyThrough(
            Reminder::class,
            CaregiverPatient::class,
            'caregiver_id', // Foreign key on caregiver_patient table
            'user_id',      // Foreign key on reminders table
            'id',           // Local key on users table
            'patient_id'    // Local key on caregiver_patient table
        );
    }

    // Get reminders created by this user
    public function createdReminders()
    {
        return $this->hasMany(Reminder::class, 'created_by');
    }

    // Get reminders assigned to this user
    public function assignedReminders()
    {
        return $this->hasMany(Reminder::class, 'user_id');
    }

    public function isCaregiver()
    {
        return $this->role === 'caregiver';
    }

    public function isPatient()
    {
        return $this->role === 'user';
    }
}
