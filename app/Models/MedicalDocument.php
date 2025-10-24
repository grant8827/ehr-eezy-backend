<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class MedicalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'uploaded_by',
        'appointment_id',
        'medical_record_id',
        'document_type',
        'category',
        'title',
        'description',
        'file_name',
        'original_file_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'is_confidential',
        'document_date',
        'tags',
        'notes',
        'business_id'
    ];

    protected $casts = [
        'document_date' => 'date',
        'is_confidential' => 'boolean',
        'file_size' => 'integer',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Document type constants
    const TYPE_LAB_REPORT = 'lab_report';
    const TYPE_IMAGING = 'imaging';
    const TYPE_PRESCRIPTION = 'prescription';
    const TYPE_DISCHARGE_SUMMARY = 'discharge_summary';
    const TYPE_CONSULTATION_NOTES = 'consultation_notes';
    const TYPE_INSURANCE_CARD = 'insurance_card';
    const TYPE_ID_DOCUMENT = 'id_document';
    const TYPE_MEDICAL_HISTORY = 'medical_history';
    const TYPE_VACCINATION_RECORD = 'vaccination_record';
    const TYPE_CONSENT_FORM = 'consent_form';
    const TYPE_OTHER = 'other';

    public static function getDocumentTypes(): array
    {
        return [
            self::TYPE_LAB_REPORT => 'Lab Report',
            self::TYPE_IMAGING => 'Medical Imaging',
            self::TYPE_PRESCRIPTION => 'Prescription',
            self::TYPE_DISCHARGE_SUMMARY => 'Discharge Summary',
            self::TYPE_CONSULTATION_NOTES => 'Consultation Notes',
            self::TYPE_INSURANCE_CARD => 'Insurance Card',
            self::TYPE_ID_DOCUMENT => 'ID Document',
            self::TYPE_MEDICAL_HISTORY => 'Medical History',
            self::TYPE_VACCINATION_RECORD => 'Vaccination Record',
            self::TYPE_CONSENT_FORM => 'Consent Form',
            self::TYPE_OTHER => 'Other'
        ];
    }

    // Category constants
    const CATEGORY_CLINICAL = 'clinical';
    const CATEGORY_ADMINISTRATIVE = 'administrative';
    const CATEGORY_DIAGNOSTIC = 'diagnostic';
    const CATEGORY_THERAPEUTIC = 'therapeutic';
    const CATEGORY_LEGAL = 'legal';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CLINICAL => 'Clinical',
            self::CATEGORY_ADMINISTRATIVE => 'Administrative',
            self::CATEGORY_DIAGNOSTIC => 'Diagnostic',
            self::CATEGORY_THERAPEUTIC => 'Therapeutic',
            self::CATEGORY_LEGAL => 'Legal'
        ];
    }

    // Scopes
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeConfidential($query, $isConfidential = true)
    {
        return $query->where('is_confidential', $isConfidential);
    }

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Accessors & Mutators
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDocumentTypeNameAttribute(): string
    {
        return self::getDocumentTypes()[$this->document_type] ?? $this->document_type;
    }

    public function getCategoryNameAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg', 'image/png', 'image/gif',
            'image/bmp', 'image/webp', 'image/svg+xml'
        ]);
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('api.medical-documents.download', $this->id);
    }

    // Helper Methods
    public function canBeViewedBy(User $user): bool
    {
        // Check if user is in the same business
        if ($user->business_id !== $this->business_id) {
            return false;
        }

        // Admin can view all
        if ($user->role === 'admin') {
            return true;
        }

        // Doctor can view their patients' documents
        if ($user->role === 'doctor') {
            return $this->patient->user_id === $user->id ||
                   $this->uploaded_by === $user->id;
        }

        // Patient can view their own documents (if not confidential or they uploaded it)
        if ($user->role === 'patient') {
            $isOwnDocument = $this->patient->user_id === $user->id;
            return $isOwnDocument && (!$this->is_confidential || $this->uploaded_by === $user->id);
        }

        return false;
    }

    public function markAsViewed(User $user): void
    {
        // Log document view for audit trail
        // This could be implemented with a document_views table
        \Log::info("Document {$this->id} viewed by user {$user->id}");
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        return true;
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            // Delete associated file when document is deleted
            $document->deleteFile();
        });
    }
}
