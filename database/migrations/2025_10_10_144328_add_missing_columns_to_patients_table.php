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
        Schema::table('patients', function (Blueprint $table) {
            $table->text('surgical_history')->nullable()->after('family_history');
            $table->string('preferred_pharmacy')->nullable()->after('social_history');
            $table->string('primary_care_physician')->nullable()->after('preferred_pharmacy');
            $table->string('referring_physician')->nullable()->after('primary_care_physician');
            $table->string('occupation')->nullable()->after('referring_physician');
            $table->string('employer')->nullable()->after('occupation');
            $table->text('notes')->nullable()->after('employer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'surgical_history',
                'preferred_pharmacy',
                'primary_care_physician',
                'referring_physician',
                'occupation',
                'employer',
                'notes'
            ]);
        });
    }
};
