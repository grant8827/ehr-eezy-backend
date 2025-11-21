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
        Schema::create('pharmacy_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('role', ['pharmacist', 'pharmacy_technician', 'pharmacy_assistant', 'manager'])->default('pharmacy_assistant');
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->date('hire_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pharmacy_id');
            $table->index(['pharmacy_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_staff');
    }
};
