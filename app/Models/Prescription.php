<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'prescribed_by',
        'appointment_id',
        'business_id',
        'medication_name',
        'generic_name',
        'strength',
        'dosage_form',
        'quantity',
        'directions',
        'frequency',
        'duration',
        'refills',
        'refills_remaining',
        'status',
        'prescribed_at',
        'start_date',
        'end_date',
        'pharmacy_name',
        'pharmacy_phone',
        'ndc_number',
        'drug_class',
        'indication',
        'notes',
        'side_effects',
        'interactions',
        'contraindications',
    ];

    protected $casts = [
        'prescribed_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'refills' => 'integer',
        'refills_remaining' => 'integer',
        'quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescriber()
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Accessors
    public function getFullMedicationNameAttribute()
    {
        $name = $this->medication_name;
        if ($this->strength) {
            $name .= ' ' . $this->strength;
        }
        if ($this->dosage_form) {
            $name .= ' (' . $this->dosage_form . ')';
        }
        return $name;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'active' => 'green',
            'pending' => 'blue',
            'completed' => 'gray',
            'expired' => 'red',
            'cancelled' => 'red',
            'discontinued' => 'yellow',
            default => 'gray'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'active' => 'Active',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'discontinued' => 'Discontinued',
            default => 'Unknown'
        };
    }

    public function getFormattedDirectionsAttribute()
    {
        $directions = $this->directions;
        if ($this->frequency) {
            $directions .= ' - ' . $this->frequency;
        }
        if ($this->duration) {
            $directions .= ' for ' . $this->duration;
        }
        return $directions;
    }

    public function getDaysRemainingAttribute()
    {
        if (!$this->end_date) {
            return null;
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        if ($endDate->isPast()) {
            return 0;
        }

        return $today->diffInDays($endDate);
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->end_date) {
            return false;
        }

        return Carbon::parse($this->end_date)->isPast();
    }

    public function getIsExpiringSoonAttribute()
    {
        if (!$this->end_date) {
            return false;
        }

        $endDate = Carbon::parse($this->end_date);
        return $endDate->isFuture() && $endDate->diffInDays(Carbon::today()) <= 7;
    }

    // Scopes
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere(function($q) {
                         $q->whereNotNull('end_date')
                           ->whereDate('end_date', '<', now());
                     });
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
                     ->whereNotNull('end_date')
                     ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeByDrugClass($query, $drugClass)
    {
        return $query->where('drug_class', $drugClass);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('prescribed_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && !$this->is_expired;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function canBeRefilled()
    {
        return $this->refills_remaining > 0 && $this->isActive();
    }

    public function processRefill()
    {
        if ($this->canBeRefilled()) {
            $this->decrement('refills_remaining');
            return true;
        }
        return false;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsDiscontinued($reason = null)
    {
        $this->update([
            'status' => 'discontinued',
            'notes' => $this->notes . ($reason ? "\nDiscontinued: " . $reason : '')
        ]);
    }

    public function markAsExpired()
    {
        $this->update(['status' => 'expired']);
    }

    // Boot method to auto-update status
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($prescription) {
            // Auto-expire prescriptions
            if ($prescription->end_date && Carbon::parse($prescription->end_date)->isPast()) {
                $prescription->status = 'expired';
            }
        });
    }

    // Constants for common dosage forms
    const DOSAGE_FORM_TABLET = 'tablet';
    const DOSAGE_FORM_CAPSULE = 'capsule';
    const DOSAGE_FORM_LIQUID = 'liquid';
    const DOSAGE_FORM_INJECTION = 'injection';
    const DOSAGE_FORM_CREAM = 'cream';
    const DOSAGE_FORM_OINTMENT = 'ointment';
    const DOSAGE_FORM_INHALER = 'inhaler';
    const DOSAGE_FORM_DROPS = 'drops';

    public static function getDosageForms()
    {
        return [
            self::DOSAGE_FORM_TABLET => 'Tablet',
            self::DOSAGE_FORM_CAPSULE => 'Capsule',
            self::DOSAGE_FORM_LIQUID => 'Liquid/Syrup',
            self::DOSAGE_FORM_INJECTION => 'Injection',
            self::DOSAGE_FORM_CREAM => 'Cream',
            self::DOSAGE_FORM_OINTMENT => 'Ointment',
            self::DOSAGE_FORM_INHALER => 'Inhaler',
            self::DOSAGE_FORM_DROPS => 'Drops',
        ];
    }

    // Common drug classes
    public static function getCommonDrugClasses()
    {
        return [
            'Antibiotics',
            'Antihypertensives',
            'Antidiabetics',
            'Analgesics',
            'Antihistamines',
            'Bronchodilators',
            'Anticoagulants',
            'Antidepressants',
            'Antiarrhythmics',
            'Diuretics',
            'Statins',
            'Proton Pump Inhibitors',
            'Beta Blockers',
            'ACE Inhibitors',
            'Calcium Channel Blockers',
        ];
    }

    // Status options
    public static function getStatusOptions()
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'discontinued' => 'Discontinued',
        ];
    }
}
