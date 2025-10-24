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
        Schema::table('medical_records', function (Blueprint $table) {
            // Only add fields that don't exist
            if (!Schema::hasColumn('medical_records', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('business_id');
            }
            if (!Schema::hasColumn('medical_records', 'status')) {
                $table->string('status')->default('active')->after('attachments');
            }
            if (!Schema::hasColumn('medical_records', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }

            // Add foreign key constraints if they don't exist
            try {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            } catch (\Exception $e) {
                // Foreign key might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            try {
                $table->dropForeign(['created_by']);
            } catch (\Exception $e) {
                // Foreign key might not exist
            }

            $columns = ['created_by', 'status', 'notes'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('medical_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
