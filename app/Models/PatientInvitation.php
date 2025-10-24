<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PatientInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'message',
        'patient_id',
        'invitation_token',
        'status',
        'expires_at',
        'sent_at',
        'registered_at',
        'resent_count',
        'created_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'registered_at' => 'datetime',
        'resent_count' => 'integer'
    ];

    protected $attributes = [
        'status' => 'pending',
        'resent_count' => 0
    ];

    /**
     * Get the user who created this invitation
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the patient if this is for an existing patient
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if invitation is still valid
     */
    public function isValid()
    {
        return $this->status === 'sent' && !$this->isExpired();
    }

    /**
     * Scope for valid invitations
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'sent')
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
