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
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Linked pharmacy account
            $table->unsignedBigInteger('business_id')->nullable(); // Associated business
            $table->string('name');
            $table->string('license_number')->unique();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('fax')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('country')->default('USA');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('operating_hours')->nullable(); // Store as JSON
            $table->string('pharmacist_in_charge')->nullable();
            $table->string('pharmacist_license')->nullable();
            $table->boolean('accepts_electronic_prescriptions')->default(true);
            $table->boolean('delivers')->default(false);
            $table->text('delivery_notes')->nullable();
            $table->json('accepted_insurances')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
