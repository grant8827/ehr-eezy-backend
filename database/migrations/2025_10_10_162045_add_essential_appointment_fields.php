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
            // Add essential missing fields
            $table->foreignId('created_by')->nullable()->after('business_id');
            $table->time('start_time')->default('09:00')->after('appointment_date');
            $table->time('end_time')->default('10:00')->after('start_time');
            $table->text('private_notes')->nullable()->after('notes');
            $table->decimal('fee', 10, 2)->nullable()->after('private_notes');
            $table->timestamp('confirmed_at')->nullable()->after('cancelled_at');
            $table->timestamp('completed_at')->nullable()->after('confirmed_at');
            $table->boolean('reminder_sent')->default(false)->after('completed_at');

            // Rename doctor_id to staff_id for consistency with our user system
            $table->renameColumn('doctor_id', 'staff_id');

            // Add foreign key constraint for created_by
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
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
