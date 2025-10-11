<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Business extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'description',
        'email',
        'phone',
        'address',
        'website',
        'license_number',
        'settings',
        'subscription_plan',
        'subscription_expires_at',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (!$business->slug) {
                $business->slug = Str::slug($business->name);
            }
        });
    }

    /**
     * Get all users belonging to this business
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all staff members (excluding patients)
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', ['admin', 'doctor', 'nurse', 'therapist', 'receptionist']);
    }

    /**
     * Get all patients
     */
    public function patients(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'patient');
    }

    /**
     * Get all doctors
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'doctor');
    }

    /**
     * Get all nurses
     */
    public function nurses(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'nurse');
    }

    /**
     * Get all therapists
     */
    public function therapists(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'therapist');
    }

    /**
     * Get business owner (first admin user)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Check if business subscription is active
     */
    public function isSubscriptionActive(): bool
    {
        if ($this->subscription_plan === 'free') {
            return true;
        }

        return $this->subscription_expires_at && $this->subscription_expires_at->isFuture();
    }

    /**
     * Get business appointments
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get business medical records
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    /**
     * Get business bills
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
