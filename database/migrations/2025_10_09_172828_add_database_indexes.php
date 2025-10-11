<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $indexName)
    {
        try {
            $connection = Schema::getConnection();
            $indexes = $connection->select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $index) {
                if ($index->Key_name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip users table indexes - they may already exist
        // Schema::table('users', function (Blueprint $table) {
        //     $table->index(['business_id', 'role']);
        //     $table->index(['email', 'business_id']);
        // });

        // Check if indexes exist before creating them
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!$this->indexExists('appointments', 'appointments_business_id_appointment_date_index')) {
                    $table->index(['business_id', 'appointment_date']);
                }
                if (!$this->indexExists('appointments', 'appointments_patient_id_status_index')) {
                    $table->index(['patient_id', 'status']);
                }
                if (!$this->indexExists('appointments', 'appointments_doctor_id_appointment_date_index')) {
                    $table->index(['doctor_id', 'appointment_date']);
                }
                if (!$this->indexExists('appointments', 'appointments_appointment_number_index')) {
                    $table->index('appointment_number');
                }
            });
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->index(['business_id', 'created_at']);
            $table->index('patient_id');
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'created_at']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->index(['patient_id', 'status']);
            $table->index(['business_id', 'due_date']);
            $table->index('bill_number');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->index(['is_active', 'subscription_plan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'subscription_plan']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['patient_id', 'status']);
            $table->dropIndex(['business_id', 'due_date']);
            $table->dropIndex(['bill_number']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropIndex(['patient_id', 'created_at']);
            $table->dropIndex(['doctor_id', 'created_at']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'created_at']);
            $table->dropIndex(['patient_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'appointment_date']);
            $table->dropIndex(['patient_id', 'status']);
            $table->dropIndex(['doctor_id', 'appointment_date']);
            $table->dropIndex(['appointment_number']);
        });

        // Skip users table indexes - they may already exist
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropIndex(['business_id', 'role']);
        //     $table->dropIndex(['email', 'business_id']);
        // });
    }
};
