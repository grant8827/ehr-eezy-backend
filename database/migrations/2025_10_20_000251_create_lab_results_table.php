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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('ordered_by'); // Doctor who ordered the test
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('business_id');

            // Lab test information
            $table->string('test_name');
            $table->string('test_code')->nullable(); // Lab code (e.g., CPT code)
            $table->string('category')->nullable(); // Blood work, Urine, etc.
            $table->text('description')->nullable();

            // Results
            $table->string('result_value');
            $table->string('unit_of_measure')->nullable(); // mg/dL, etc.
            $table->string('reference_range')->nullable(); // Normal range
            $table->enum('status', ['normal', 'abnormal', 'critical', 'pending'])->default('pending');
            $table->string('flag')->nullable(); // H (High), L (Low), etc.

            // Dates
            $table->timestamp('ordered_at');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('resulted_at')->nullable();

            // Lab information
            $table->string('lab_name')->nullable();
            $table->string('lab_reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->text('interpretation')->nullable();

            // File attachments
            $table->json('attachments')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('ordered_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');

            // Indexes
            $table->index(['patient_id', 'ordered_at']);
            $table->index(['business_id', 'status']);
            $table->index('test_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
