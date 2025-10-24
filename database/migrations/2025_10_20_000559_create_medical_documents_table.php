<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_documents', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('uploaded_by'); // User who uploaded the document
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('medical_record_id')->nullable();

            // Document classification
            $table->enum('document_type', [
                'lab_report', 'imaging', 'prescription', 'discharge_summary',
                'consultation_notes', 'insurance_card', 'id_document',
                'medical_history', 'vaccination_record', 'consent_form', 'other'
            ]);
            $table->enum('category', [
                'clinical', 'administrative', 'diagnostic', 'therapeutic', 'legal'
            ]);

            // Document metadata
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('document_date')->nullable(); // Date the document was created/issued
            $table->json('tags')->nullable(); // Searchable tags
            $table->text('notes')->nullable();
            $table->boolean('is_confidential')->default(false);

            // File information
            $table->string('file_name'); // Stored filename
            $table->string('original_file_name'); // Original uploaded filename
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes
            $table->string('mime_type', 100)->nullable();
            $table->string('file_extension', 10)->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('medical_record_id')->references('id')->on('medical_records')->onDelete('set null');

            // Indexes for better performance
            $table->index(['business_id', 'patient_id']);
            $table->index(['document_type', 'category']);
            $table->index(['patient_id', 'document_date']);
            $table->index(['uploaded_by', 'created_at']);
            $table->index('is_confidential');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_documents');
    }
};
