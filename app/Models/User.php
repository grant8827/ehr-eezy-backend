<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'date_of_birth',
        'gender',
        'is_active',
        'business_id',
        'invitation_token',
        'invitation_sent_at',
        'invitation_accepted_at',
        'is_business_owner',
        'license_number',
        'specialization',
        'qualifications',
        'years_of_experience',
        'profile_picture',
        'notification_preferences',
        'deactivated_at',
        'deactivation_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'is_business_owner' => 'boolean',
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    // Role helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isDoctor()
    {
        return $this->role === 'doctor';
    }

    public function isPatient()
    {
        return $this->role === 'patient';
    }

    public function isNurse()
    {
        return $this->role === 'nurse';
    }

    public function isReceptionist()
    {
        return $this->role === 'receptionist';
    }

    public function isTherapist()
    {
        return $this->role === 'therapist';
    }

    public function isStaff()
    {
        return in_array($this->role, ['admin', 'doctor', 'nurse', 'therapist', 'receptionist']);
    }

    // Relationships
    public function patientRecord()
    {
        return $this->hasOne(Patient::class);
    }

    public function doctorProfile()
    {
        return $this->hasOne(Doctor::class);
    }

    public function therapistProfile()
    {
        return $this->hasOne(Therapist::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // Business relationship
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    // Scoped queries for multi-tenancy
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeStaff($query)
    {
        return $query->whereIn('role', ['admin', 'doctor', 'nurse', 'therapist', 'receptionist']);
    }

    public function scopePatients($query)
    {
        return $query->where('role', 'patient');
    }

    // Accessor for profile picture URL
    public function getProfilePictureUrlAttribute()
    {
        if (!$this->profile_picture) {
            return null;
        }

        return asset('storage/' . $this->profile_picture);
    }
}
