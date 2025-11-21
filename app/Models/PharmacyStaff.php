<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyStaff extends Model
{
    use SoftDeletes;

    protected $table = 'pharmacy_staff';

    protected $fillable = [
        'pharmacy_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'role',
        'license_number',
        'license_expiry',
        'status',
        'hire_date',
        'notes',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'hire_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the pharmacy that owns the staff member
     */
    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    /**
     * Get the full name of the staff member
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if license is expired or expiring soon
     */
    public function isLicenseExpiringSoon($days = 30)
    {
        if (!$this->license_expiry) {
            return false;
        }

        return $this->license_expiry <= now()->addDays($days);
    }

    /**
     * Check if staff member is active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Scope to filter active staff
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter by pharmacy
     */
    public function scopeForPharmacy($query, $pharmacyId)
    {
        return $query->where('pharmacy_id', $pharmacyId);
    }
}
