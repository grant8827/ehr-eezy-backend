<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Therapist extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'license_number',
        'specialization',
        'qualifications',
        'years_of_experience',
        'bio',
        'availability_hours',
        'consultation_fee',
        'telehealth_enabled',
        'therapy_types',
        'certifications',
    ];

    protected $casts = [
        'availability_hours' => 'array',
        'telehealth_enabled' => 'boolean',
        'therapy_types' => 'array',
        'consultation_fee' => 'decimal:2',
    ];

    /**
     * Get the user that owns the therapist profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business this therapist belongs to
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get appointments for this therapist
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'user_id');
    }

    /**
     * Get patients treated by this therapist
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'assigned_therapist_id', 'user_id');
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Check if therapist is available for telehealth
     */
    public function isTelehealthAvailable(): bool
    {
        return $this->telehealth_enabled;
    }

    /**
     * Get formatted therapy types
     */
    public function getTherapyTypesListAttribute()
    {
        return $this->therapy_types ? implode(', ', $this->therapy_types) : '';
    }
}
