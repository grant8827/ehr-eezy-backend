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
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the existing foreign key constraint using the actual constraint name
            DB::statement('ALTER TABLE appointments DROP FOREIGN KEY appointments_doctor_id_foreign');

            // Add the new foreign key constraint pointing to users table
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the users foreign key constraint
            $table->dropForeign(['staff_id']);

            // Add back the doctors foreign key constraint
            $table->foreign('staff_id')->references('id')->on('doctors')->onDelete('cascade');
        });
    }
};
