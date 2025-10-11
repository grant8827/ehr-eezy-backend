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
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('users')->onDelete('cascade'); // Provider/staff member
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // Who scheduled it

            // Appointment Details
            $table->string('appointment_number')->unique(); // e.g., APT000001
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes'); // Duration in minutes

            // Appointment Type & Status
            $table->enum('type', [
                'consultation',
                'follow_up',
                'therapy_session',
                'assessment',
                'treatment',
                'check_up',
                'emergency',
                'other'
            ])->default('consultation');

            $table->enum('status', [
                'scheduled',     // Initial state
                'confirmed',     // Patient confirmed
                'in_progress',   // Currently happening
                'completed',     // Successfully completed
                'cancelled',     // Cancelled by patient/staff
                'no_show',       // Patient didn't show up
                'rescheduled'    // Moved to different time
            ])->default('scheduled');

            // Additional Information
            $table->text('notes')->nullable(); // Appointment notes/reason
            $table->text('private_notes')->nullable(); // Staff-only notes
            $table->decimal('fee', 10, 2)->nullable(); // Appointment fee
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable(); // daily, weekly, monthly
            $table->integer('recurring_count')->nullable(); // How many times

            // Scheduling Information
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Reminders
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['business_id', 'appointment_date']);
            $table->index(['staff_id', 'appointment_date']);
            $table->index(['patient_id', 'status']);
            $table->index(['appointment_date', 'start_time']);
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
