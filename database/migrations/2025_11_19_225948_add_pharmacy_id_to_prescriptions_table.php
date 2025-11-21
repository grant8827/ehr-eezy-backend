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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('pharmacy_id')->nullable()->after('business_id');
            $table->enum('pharmacy_status', ['pending', 'sent', 'received', 'filled', 'picked_up', 'delivered', 'cancelled'])->default('pending')->after('status');
            $table->timestamp('sent_to_pharmacy_at')->nullable()->after('prescribed_at');
            $table->timestamp('filled_at')->nullable()->after('sent_to_pharmacy_at');
            $table->timestamp('picked_up_at')->nullable()->after('filled_at');

            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
            $table->dropColumn(['pharmacy_id', 'pharmacy_status', 'sent_to_pharmacy_at', 'filled_at', 'picked_up_at']);
        });
    }
};
