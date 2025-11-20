<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pharmacy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_id',
        'name',
        'license_number',
        'email',
        'phone',
        'fax',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'latitude',
        'longitude',
        'operating_hours',
        'pharmacist_in_charge',
        'pharmacist_license',
        'accepts_electronic_prescriptions',
        'delivers',
        'delivery_notes',
        'accepted_insurances',
        'status',
        'notes',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'accepted_insurances' => 'array',
        'accepts_electronic_prescriptions' => 'boolean',
        'delivers' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAcceptsElectronic($query)
    {
        return $query->where('accepts_electronic_prescriptions', true);
    }

    public function scopeDelivers($query)
    {
        return $query->where('delivers', true);
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    public function scopeInState($query, $state)
    {
        return $query->where('state', $state);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusMiles = 10)
    {
        // Haversine formula for distance calculation
        $earthRadius = 3959; // miles
        
        return $query->selectRaw("*, 
            ( {$earthRadius} * acos( cos( radians(?) ) * 
            cos( radians( latitude ) ) * 
            cos( radians( longitude ) - radians(?) ) + 
            sin( radians(?) ) * 
            sin( radians( latitude ) ) ) ) AS distance", 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusMiles)
            ->orderBy('distance');
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->zip_code}";
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    // Helper methods
    public function isOpen($dayOfWeek = null, $time = null)
    {
        if (!$this->operating_hours) {
            return false;
        }

        $dayOfWeek = $dayOfWeek ?? now()->dayOfWeek;
        $time = $time ?? now()->format('H:i');

        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $day = $days[$dayOfWeek];

        if (!isset($this->operating_hours[$day])) {
            return false;
        }

        $hours = $this->operating_hours[$day];
        if ($hours['closed'] ?? false) {
            return false;
        }

        return $time >= $hours['open'] && $time <= $hours['close'];
    }

    public function getPendingPrescriptionsCount()
    {
        return $this->prescriptions()
            ->where('pharmacy_status', 'pending')
            ->count();
    }

    public function getReceivedPrescriptionsCount()
    {
        return $this->prescriptions()
            ->where('pharmacy_status', 'received')
            ->count();
    }
}
