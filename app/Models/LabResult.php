<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'ordered_by',
        'appointment_id',
        'business_id',
        'test_name',
        'test_code',
        'category',
        'description',
        'result_value',
        'unit_of_measure',
        'reference_range',
        'status',
        'flag',
        'ordered_at',
        'collected_at',
        'resulted_at',
        'lab_name',
        'lab_reference_number',
        'notes',
        'interpretation',
        'attachments',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'collected_at' => 'datetime',
        'resulted_at' => 'datetime',
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
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
    public function getFormattedResultAttribute()
    {
        $result = $this->result_value;
        if ($this->unit_of_measure) {
            $result .= ' ' . $this->unit_of_measure;
        }
        if ($this->flag) {
            $result .= ' (' . $this->flag . ')';
        }
        return $result;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'normal' => 'green',
            'abnormal' => 'yellow',
            'critical' => 'red',
            'pending' => 'blue',
            default => 'gray'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'normal' => 'Normal',
            'abnormal' => 'Abnormal',
            'critical' => 'Critical',
            'pending' => 'Pending',
            default => 'Unknown'
        };
    }

    public function getFormattedTimestampAttribute()
    {
        if ($this->resulted_at) {
            return $this->resulted_at->format('M j, Y g:i A');
        }
        if ($this->collected_at) {
            return 'Collected: ' . $this->collected_at->format('M j, Y g:i A');
        }
        if ($this->ordered_at) {
            return 'Ordered: ' . $this->ordered_at->format('M j, Y g:i A');
        }
        return null;
    }

    // Scopes
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('ordered_at', '>=', now()->subDays($days));
    }

    public function scopeAbnormal($query)
    {
        return $query->whereIn('status', ['abnormal', 'critical']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper methods
    public function isAbnormal()
    {
        return in_array($this->status, ['abnormal', 'critical']);
    }

    public function isCritical()
    {
        return $this->status === 'critical';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isCompleted()
    {
        return $this->resulted_at !== null;
    }

    public function markAsCompleted($resultValue, $status = 'normal', $additionalData = [])
    {
        $this->update(array_merge([
            'result_value' => $resultValue,
            'status' => $status,
            'resulted_at' => now(),
        ], $additionalData));
    }

    // Constants for common test categories
    const CATEGORY_BLOOD_WORK = 'Blood Work';
    const CATEGORY_URINE = 'Urine Tests';
    const CATEGORY_CARDIAC = 'Cardiac Markers';
    const CATEGORY_LIVER_FUNCTION = 'Liver Function';
    const CATEGORY_KIDNEY_FUNCTION = 'Kidney Function';
    const CATEGORY_THYROID = 'Thyroid Function';
    const CATEGORY_LIPIDS = 'Lipid Panel';
    const CATEGORY_DIABETES = 'Diabetes Monitoring';
    const CATEGORY_IMMUNOLOGY = 'Immunology';
    const CATEGORY_MICROBIOLOGY = 'Microbiology';

    public static function getCategories()
    {
        return [
            self::CATEGORY_BLOOD_WORK => 'Blood Work',
            self::CATEGORY_URINE => 'Urine Tests',
            self::CATEGORY_CARDIAC => 'Cardiac Markers',
            self::CATEGORY_LIVER_FUNCTION => 'Liver Function',
            self::CATEGORY_KIDNEY_FUNCTION => 'Kidney Function',
            self::CATEGORY_THYROID => 'Thyroid Function',
            self::CATEGORY_LIPIDS => 'Lipid Panel',
            self::CATEGORY_DIABETES => 'Diabetes Monitoring',
            self::CATEGORY_IMMUNOLOGY => 'Immunology',
            self::CATEGORY_MICROBIOLOGY => 'Microbiology',
        ];
    }

    // Common lab tests
    public static function getCommonTests()
    {
        return [
            'CBC' => 'Complete Blood Count',
            'CMP' => 'Comprehensive Metabolic Panel',
            'BMP' => 'Basic Metabolic Panel',
            'TSH' => 'Thyroid Stimulating Hormone',
            'HbA1c' => 'Hemoglobin A1C',
            'PSA' => 'Prostate Specific Antigen',
            'Glucose' => 'Blood Glucose',
            'Cholesterol' => 'Total Cholesterol',
            'HDL' => 'HDL Cholesterol',
            'LDL' => 'LDL Cholesterol',
            'Triglycerides' => 'Triglycerides',
            'Creatinine' => 'Serum Creatinine',
            'BUN' => 'Blood Urea Nitrogen',
            'ALT' => 'Alanine Aminotransferase',
            'AST' => 'Aspartate Aminotransferase',
        ];
    }
}
