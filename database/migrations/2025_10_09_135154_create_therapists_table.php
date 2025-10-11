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
        Schema::create('therapists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->unique();
            $table->string('specialization'); // Physical Therapy, Occupational Therapy, Speech Therapy, etc.
            $table->text('qualifications');
            $table->integer('years_of_experience')->default(0);
            $table->text('bio')->nullable();
            $table->json('availability_hours')->nullable();
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->boolean('telehealth_enabled')->default(false);
            $table->json('therapy_types')->nullable(); // Array of therapy types offered
            $table->text('certifications')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('therapists');
    }
};
