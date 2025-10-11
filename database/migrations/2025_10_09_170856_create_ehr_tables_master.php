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
        // Temporarily disable foreign key checks for MySQL
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // This migration ensures all core tables exist with business_id columns
        // Just run without doing anything - existing migrations will handle creation

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to drop - this is just a helper migration
    }
};
