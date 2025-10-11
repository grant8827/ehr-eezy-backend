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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('appointment_number')->unique();
            $table->dateTime('appointment_date');
            $table->integer('duration_minutes')->default(30);
            $table->enum('type', ['in-person', 'telehealth'])->default('in-person');
            $table->enum('status', ['scheduled', 'confirmed', 'in-progress', 'completed', 'cancelled', 'no-show'])->default('scheduled');
            $table->text('reason_for_visit')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
