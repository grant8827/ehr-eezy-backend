<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'record_type',
        'chief_complaint',
        'history_of_present_illness',
        'physical_examination',
        'diagnosis',
        'treatment_plan',
        'follow_up_instructions',
        'vital_signs',
        'attachments',
        'business_id',
        'created_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function documents()
    {
        return $this->hasMany(MedicalDocument::class);
    }

    // Scopes
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    // Accessors
    public function getFormattedVitalSignsAttribute()
    {
        if (!$this->vital_signs) {
            return null;
        }

        $vitals = $this->vital_signs;
        $formatted = [];

        if (isset($vitals['blood_pressure'])) {
            $formatted['Blood Pressure'] = $vitals['blood_pressure'] . ' mmHg';
        }
        if (isset($vitals['heart_rate'])) {
            $formatted['Heart Rate'] = $vitals['heart_rate'] . ' bpm';
        }
        if (isset($vitals['temperature'])) {
            $formatted['Temperature'] = $vitals['temperature'] . '°F';
        }
        if (isset($vitals['respiratory_rate'])) {
            $formatted['Respiratory Rate'] = $vitals['respiratory_rate'] . ' breaths/min';
        }
        if (isset($vitals['weight'])) {
            $formatted['Weight'] = $vitals['weight'] . ' lbs';
        }
        if (isset($vitals['height'])) {
            $formatted['Height'] = $vitals['height'] . ' inches';
        }

        return $formatted;
    }

    // Constants for record types
    const TYPE_CONSULTATION = 'consultation';
    const TYPE_LAB_RESULT = 'lab_result';
    const TYPE_IMAGING = 'imaging';
    const TYPE_PRESCRIPTION = 'prescription';
    const TYPE_VITAL_SIGNS = 'vital_signs';
    const TYPE_PROCEDURE = 'procedure';
    const TYPE_FOLLOW_UP = 'follow_up';

    public static function getRecordTypes()
    {
        return [
            self::TYPE_CONSULTATION => 'Consultation',
            self::TYPE_LAB_RESULT => 'Lab Result',
            self::TYPE_IMAGING => 'Imaging/Radiology',
            self::TYPE_PRESCRIPTION => 'Prescription',
            self::TYPE_VITAL_SIGNS => 'Vital Signs',
            self::TYPE_PROCEDURE => 'Procedure',
            self::TYPE_FOLLOW_UP => 'Follow-up',
        ];
    }
}
