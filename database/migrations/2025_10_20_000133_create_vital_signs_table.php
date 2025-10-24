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
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); // Staff member who recorded
            $table->unsignedBigInteger('business_id');

            // Vital signs data
            $table->string('systolic_bp')->nullable(); // e.g., "120"
            $table->string('diastolic_bp')->nullable(); // e.g., "80"
            $table->decimal('heart_rate', 5, 2)->nullable(); // beats per minute
            $table->decimal('temperature', 5, 2)->nullable(); // Fahrenheit
            $table->decimal('respiratory_rate', 5, 2)->nullable(); // breaths per minute
            $table->decimal('oxygen_saturation', 5, 2)->nullable(); // percentage
            $table->decimal('weight', 6, 2)->nullable(); // pounds
            $table->decimal('height', 5, 2)->nullable(); // inches
            $table->decimal('bmi', 5, 2)->nullable(); // calculated BMI

            // Additional measurements
            $table->string('pain_scale')->nullable(); // 1-10 scale or description
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->string('status')->default('active'); // active, corrected, deleted

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Indexes
            $table->index(['patient_id', 'recorded_at']);
            $table->index('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vital_signs');
    }
};
