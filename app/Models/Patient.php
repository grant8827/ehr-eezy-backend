<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'created_by',
        'patient_id',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'social_security',
        'preferred_language',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_address',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_group_number',
        'insurance_subscriber_name',
        'insurance_subscriber_dob',
        'secondary_insurance_provider',
        'secondary_insurance_policy',
        'blood_type',
        'height',
        'weight',
        'allergies',
        'medications',
        'medical_history',
        'chronic_conditions',
        'family_history',
        'surgical_history',
        'social_history',
        'preferred_pharmacy',
        'primary_care_physician',
        'referring_physician',
        'occupation',
        'employer',
        'notes',
        'status',
    ];

    protected $casts = [
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'date_of_birth' => 'date',
        'insurance_subscriber_dob' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function documents()
    {
        return $this->hasMany(MedicalDocument::class);
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSigns::class);
    }

    public function labResults()
    {
        return $this->hasMany(LabResult::class);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
    }

    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }

    public function getAgeAttribute()
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->diffInYears(now());
        }
        return null;
    }
}
