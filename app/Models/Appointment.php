<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'patient_id',
        'staff_id',
        'created_by',
        'appointment_number',
        'appointment_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'type',
        'status',
        'reason_for_visit', // From existing table
        'notes',
        'private_notes',
        'fee',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by', // From existing table
        'reminder_sent',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'fee' => 'decimal:2',
        'reminder_sent' => 'boolean',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Define appointment types (based on existing table structure)
    public static function getTypes()
    {
        return [
            'in-person' => 'In-Person',
            'telehealth' => 'Telehealth'
        ];
    }

    // Define appointment statuses
    public static function getStatuses()
    {
        return [
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No Show',
            'rescheduled' => 'Rescheduled'
        ];
    }

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
                    ->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function scopeToday($query)
    {
        return $query->where('appointment_date', now()->toDateString());
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    // Accessors
    public function getFullDateTimeAttribute()
    {
        return $this->appointment_date->format('Y-m-d') . ' ' . $this->start_time;
    }

    public function getFormattedDateAttribute()
    {
        return $this->appointment_date->format('M j, Y');
    }

    public function getFormattedTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' .
               Carbon::parse($this->end_time)->format('g:i A');
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'scheduled' => 'blue',
            'confirmed' => 'green',
            'in_progress' => 'yellow',
            'completed' => 'gray',
            'cancelled' => 'red',
            'no_show' => 'red',
            'rescheduled' => 'orange',
            default => 'gray'
        };
    }

    public function getIsUpcomingAttribute()
    {
        return $this->appointment_date >= now()->toDateString() &&
               in_array($this->status, ['scheduled', 'confirmed']);
    }

    public function getIsPastAttribute()
    {
        return $this->appointment_date < now()->toDateString() ||
               in_array($this->status, ['completed', 'no_show']);
    }

    // Methods
    public function canBeCancelled()
    {
        return in_array($this->status, ['scheduled', 'confirmed']) &&
               $this->appointment_date >= now()->toDateString();
    }

    public function canBeRescheduled()
    {
        return in_array($this->status, ['scheduled', 'confirmed']) &&
               $this->appointment_date >= now()->toDateString();
    }

    public function canBeCompleted()
    {
        return in_array($this->status, ['scheduled', 'confirmed', 'in_progress']);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function markAsCancelled($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);
    }

    public function markAsNoShow()
    {
        $this->update([
            'status' => 'no_show'
        ]);
    }

    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);
    }
}
