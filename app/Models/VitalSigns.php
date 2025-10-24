<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class VitalSigns extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'recorded_by',
        'business_id',
        'systolic_bp',
        'diastolic_bp',
        'heart_rate',
        'temperature',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
        'bmi',
        'pain_scale',
        'notes',
        'recorded_at',
        'status',
    ];

    protected $casts = [
        'heart_rate' => 'decimal:2',
        'temperature' => 'decimal:2',
        'respiratory_rate' => 'decimal:2',
        'oxygen_saturation' => 'decimal:2',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'bmi' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Accessors
    public function getBloodPressureAttribute()
    {
        if ($this->systolic_bp && $this->diastolic_bp) {
            return $this->systolic_bp . '/' . $this->diastolic_bp;
        }
        return null;
    }

    public function getFormattedTemperatureAttribute()
    {
        if ($this->temperature) {
            return number_format($this->temperature, 1) . '°F';
        }
        return null;
    }

    public function getFormattedHeartRateAttribute()
    {
        if ($this->heart_rate) {
            return number_format($this->heart_rate, 0) . ' bpm';
        }
        return null;
    }

    public function getFormattedRespiratoryRateAttribute()
    {
        if ($this->respiratory_rate) {
            return number_format($this->respiratory_rate, 0) . ' breaths/min';
        }
        return null;
    }

    public function getFormattedOxygenSaturationAttribute()
    {
        if ($this->oxygen_saturation) {
            return number_format($this->oxygen_saturation, 1) . '%';
        }
        return null;
    }

    public function getFormattedWeightAttribute()
    {
        if ($this->weight) {
            return number_format($this->weight, 1) . ' lbs';
        }
        return null;
    }

    public function getFormattedHeightAttribute()
    {
        if ($this->height) {
            $feet = floor($this->height / 12);
            $inches = $this->height % 12;
            return $feet . "' " . number_format($inches, 0) . '"';
        }
        return null;
    }

    // Mutators
    public function setRecordedAtAttribute($value)
    {
        $this->attributes['recorded_at'] = $value ?? now();
    }

    // Calculate BMI automatically when weight and height are set
    public function calculateBmi()
    {
        if ($this->weight && $this->height) {
            // BMI = (weight in lbs / (height in inches)^2) * 703
            $bmi = ($this->weight / ($this->height * $this->height)) * 703;
            $this->bmi = round($bmi, 2);
            return $this->bmi;
        }
        return null;
    }

    // Scopes
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Boot method to auto-calculate BMI
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($vitalSigns) {
            $vitalSigns->calculateBmi();
        });
    }

    // Helper methods for normal ranges
    public function isBloodPressureNormal()
    {
        if (!$this->systolic_bp || !$this->diastolic_bp) {
            return null;
        }

        $systolic = intval($this->systolic_bp);
        $diastolic = intval($this->diastolic_bp);

        return $systolic < 120 && $diastolic < 80;
    }

    public function isHeartRateNormal()
    {
        if (!$this->heart_rate) {
            return null;
        }

        return $this->heart_rate >= 60 && $this->heart_rate <= 100;
    }

    public function isTemperatureNormal()
    {
        if (!$this->temperature) {
            return null;
        }

        return $this->temperature >= 97.0 && $this->temperature <= 99.5;
    }

    public function getBmiCategory()
    {
        if (!$this->bmi) {
            return null;
        }

        if ($this->bmi < 18.5) {
            return 'Underweight';
        } elseif ($this->bmi < 25) {
            return 'Normal weight';
        } elseif ($this->bmi < 30) {
            return 'Overweight';
        } else {
            return 'Obese';
        }
    }
}
