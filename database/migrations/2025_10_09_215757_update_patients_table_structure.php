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
        Schema::table('patients', function (Blueprint $table) {
            // Add business_id if it doesn't exist
            if (!Schema::hasColumn('patients', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }

            // Add personal information fields
            if (!Schema::hasColumn('patients', 'first_name')) {
                $table->string('first_name')->after('patient_id');
            }
            if (!Schema::hasColumn('patients', 'last_name')) {
                $table->string('last_name')->after('first_name');
            }
            $table->string('middle_name')->nullable()->after('last_name');
            $table->date('date_of_birth')->after('middle_name');
            $table->enum('gender', ['male', 'female', 'other'])->after('date_of_birth');
            $table->string('marital_status')->nullable()->after('gender');
            $table->string('social_security')->nullable()->after('marital_status');
            $table->string('preferred_language')->nullable()->after('social_security');

            // Add contact information
            $table->string('email')->nullable()->after('preferred_language');
            $table->string('phone')->nullable()->after('email');
            $table->string('alternate_phone')->nullable()->after('phone');
            $table->text('address')->nullable()->after('alternate_phone');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->string('country')->nullable()->default('United States')->after('zip_code');

            // Update emergency contact fields
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            $table->text('emergency_contact_address')->nullable()->after('emergency_contact_phone');

            // Add insurance fields
            $table->string('insurance_group_number')->nullable()->after('insurance_policy_number');
            $table->string('insurance_subscriber_name')->nullable()->after('insurance_group_number');
            $table->date('insurance_subscriber_dob')->nullable()->after('insurance_subscriber_name');
            $table->string('secondary_insurance_provider')->nullable()->after('insurance_subscriber_dob');
            $table->string('secondary_insurance_policy')->nullable()->after('secondary_insurance_provider');

            // Add medical fields
            $table->json('medications')->nullable()->after('current_medications');
            $table->text('chronic_conditions')->nullable()->after('medical_history');
            $table->text('family_history')->nullable()->after('chronic_conditions');
            $table->text('social_history')->nullable()->after('family_history');

            // Add status field
            $table->enum('status', ['active', 'inactive'])->default('active')->after('social_history');

            // Remove old field
            $table->dropColumn('current_medications');
        });

        // Update existing records to have business_id from user's business
        DB::statement('UPDATE patients SET business_id = (SELECT business_id FROM users WHERE users.id = patients.user_id) WHERE business_id IS NULL');

        // Make business_id not nullable
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn([
                'business_id',
                'first_name',
                'last_name',
                'middle_name',
                'date_of_birth',
                'gender',
                'marital_status',
                'social_security',
                'preferred_language',
                'email',
                'phone',
                'alternate_phone',
                'address',
                'city',
                'state',
                'zip_code',
                'country',
                'emergency_contact_relationship',
                'emergency_contact_address',
                'insurance_group_number',
                'insurance_subscriber_name',
                'insurance_subscriber_dob',
                'secondary_insurance_provider',
                'secondary_insurance_policy',
                'medications',
                'chronic_conditions',
                'family_history',
                'social_history',
                'status'
            ]);

            // Add back old field
            $table->text('current_medications')->nullable();
        });
    }
};
