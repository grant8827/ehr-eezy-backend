<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add business relationship
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');

            // Update role enum to include therapist
            $table->enum('role_new', ['admin', 'doctor', 'nurse', 'therapist', 'patient', 'receptionist'])->default('patient');

            // Add staff invitation fields
            $table->string('invitation_token')->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->boolean('is_business_owner')->default(false);

            // Add professional fields for staff
            $table->string('license_number')->nullable();
            $table->text('specialization')->nullable();
            $table->text('qualifications')->nullable();
            $table->integer('years_of_experience')->nullable();
        });

        // Copy data from old role column to new one
        DB::statement("UPDATE users SET role_new = role");

        // Drop old role column and rename new one
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn([
                'business_id',
                'invitation_token',
                'invitation_sent_at',
                'invitation_accepted_at',
                'is_business_owner',
                'license_number',
                'specialization',
                'qualifications',
                'years_of_experience'
            ]);

            // Restore original role enum
            $table->enum('role_old', ['admin', 'doctor', 'nurse', 'patient', 'receptionist'])->default('patient');
        });

        DB::statement("UPDATE users SET role_old = role");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->renameColumn('role_old', 'role');
        });
    }
};
