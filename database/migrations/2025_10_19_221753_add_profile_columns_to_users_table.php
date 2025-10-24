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
        Schema::table('users', function (Blueprint $table) {
            // Add missing profile-related columns
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');

            // Rename profile_image to profile_picture for consistency
            $table->renameColumn('profile_image', 'profile_picture');

            // Add notification preferences as JSON
            $table->json('notification_preferences')->nullable()->after('profile_picture');

            // Add account deactivation fields
            $table->timestamp('deactivated_at')->nullable()->after('notification_preferences');
            $table->text('deactivation_reason')->nullable()->after('deactivated_at');

            // Update gender enum to include new options
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'state',
                'zip_code',
                'notification_preferences',
                'deactivated_at',
                'deactivation_reason'
            ]);

            // Rename back to profile_image
            $table->renameColumn('profile_picture', 'profile_image');

            // Revert gender enum
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->change();
        });
    }
};
