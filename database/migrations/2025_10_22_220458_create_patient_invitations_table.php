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
        Schema::create('patient_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable(); // For existing patients
            $table->string('invitation_token')->unique();
            $table->enum('status', ['pending', 'sent', 'registered', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->integer('resent_count')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['email', 'invitation_token']);
            $table->index(['status', 'expires_at']);
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_invitations');
    }
};
