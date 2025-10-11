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
        Schema::table('appointments', function (Blueprint $table) {
            // Rename doctor_id to staff_id
            $table->renameColumn('doctor_id', 'staff_id');

            // Add missing columns
            $table->foreignId('created_by')->nullable()->after('business_id')->constrained('users')->onDelete('cascade');
            $table->time('start_time')->nullable()->after('appointment_date');
            $table->time('end_time')->nullable()->after('start_time');

            // Modify appointment_date to be date only (currently datetime)
            $table->date('appointment_date_new')->after('appointment_date');

            // Update type enum to include more appointment types
            $table->enum('type_new', [
                'consultation',
                'follow_up',
                'therapy_session',
                'assessment',
                'treatment',
                'check_up',
                'emergency',
                'other'
            ])->default('consultation')->after('type');

            // Add new fields
            $table->text('private_notes')->nullable()->after('notes');
            $table->decimal('fee', 10, 2)->nullable()->after('private_notes');
            $table->boolean('is_recurring')->default(false)->after('fee');
            $table->string('recurring_pattern')->nullable()->after('is_recurring');
            $table->integer('recurring_count')->nullable()->after('recurring_pattern');
            $table->timestamp('confirmed_at')->nullable()->after('recurring_count');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->boolean('reminder_sent')->default(false)->after('cancelled_by');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_sent');

            // Add indexes
            $table->index(['business_id', 'appointment_date_new'], 'idx_business_date');
            $table->index(['staff_id', 'appointment_date_new'], 'idx_staff_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
