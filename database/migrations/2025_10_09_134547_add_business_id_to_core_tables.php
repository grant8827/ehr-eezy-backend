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
        // Add business_id to patients table
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to doctors table
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to medical_records table
        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to bills table
        Schema::table('bills', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to messages table
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to prescriptions table
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to telehealth_sessions table
        Schema::table('telehealth_sessions', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add business_id to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['patients', 'doctors', 'medical_records', 'bills', 'messages', 'prescriptions', 'telehealth_sessions', 'payments'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['business_id']);
                $table->dropColumn('business_id');
            });
        }
    }
};
