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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->unique();
            $table->string('specialization');
            $table->text('qualifications')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->text('bio')->nullable();
            $table->json('availability_hours')->nullable(); // Store working hours as JSON
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->boolean('telehealth_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
