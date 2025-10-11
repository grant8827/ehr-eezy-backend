<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'specialization',
        'qualifications',
        'years_of_experience',
        'bio',
        'availability_hours',
        'consultation_fee',
        'telehealth_enabled',
    ];

    protected $casts = [
        'availability_hours' => 'array',
        'consultation_fee' => 'decimal:2',
        'telehealth_enabled' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function telehealthSessions()
    {
        return $this->hasManyThrough(TelehealthSession::class, Appointment::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return 'Dr. ' . $this->user->first_name . ' ' . $this->user->last_name;
    }

    public function isAvailableOn($date, $time)
    {
        // Logic to check if doctor is available on specific date and time
        // This would be implemented based on availability_hours and existing appointments
        return true;
    }
}
