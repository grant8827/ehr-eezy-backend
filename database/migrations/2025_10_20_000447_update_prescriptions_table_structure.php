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
        Schema::table('prescriptions', function (Blueprint $table) {
            // Basic prescription information
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('prescribed_by'); // Doctor/Provider who prescribed
            $table->unsignedBigInteger('appointment_id')->nullable();

            // Medication details
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('strength')->nullable(); // e.g., "5mg", "10mg/ml"
            $table->string('dosage_form')->nullable(); // tablet, capsule, liquid, etc.
            $table->decimal('quantity', 8, 2)->nullable(); // Number of pills/volume
            $table->text('directions'); // How to take the medication
            $table->string('frequency')->nullable(); // Once daily, twice daily, etc.
            $table->string('duration')->nullable(); // 7 days, 30 days, etc.

            // Refill information
            $table->integer('refills')->default(0);
            $table->integer('refills_remaining')->default(0);

            // Status and dates
            $table->enum('status', ['pending', 'active', 'completed', 'expired', 'cancelled', 'discontinued'])->default('pending');
            $table->timestamp('prescribed_at');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Pharmacy information
            $table->string('pharmacy_name')->nullable();
            $table->string('pharmacy_phone')->nullable();

            // Additional medication information
            $table->string('ndc_number')->nullable(); // National Drug Code
            $table->string('drug_class')->nullable(); // Antibiotic, antihypertensive, etc.
            $table->text('indication')->nullable(); // What condition this treats
            $table->text('notes')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('interactions')->nullable();
            $table->text('contraindications')->nullable();

            // Foreign key constraints
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('prescribed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');

            // Indexes
            $table->index(['patient_id', 'prescribed_at']);
            $table->index(['status', 'end_date']);
            $table->index('prescribed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['prescribed_by']);
            $table->dropForeign(['appointment_id']);

            // Drop columns
            $columns = [
                'patient_id', 'prescribed_by', 'appointment_id',
                'medication_name', 'generic_name', 'strength', 'dosage_form',
                'quantity', 'directions', 'frequency', 'duration',
                'refills', 'refills_remaining', 'status', 'prescribed_at',
                'start_date', 'end_date', 'pharmacy_name', 'pharmacy_phone',
                'ndc_number', 'drug_class', 'indication', 'notes',
                'side_effects', 'interactions', 'contraindications'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('prescriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
