<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaregiverPatient extends Model
{
    protected $table = 'caregiver_patient';

    protected $fillable = [
        'caregiver_id',
        'patient_id'
    ];

    public function caregiver()
    {
        return $this->belongsTo(User::class, 'caregiver_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
