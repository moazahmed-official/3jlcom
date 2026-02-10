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
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status', 50)->default('active')->after('is_verified'); // active, banned, suspended
            }
            
            // Add ban tracking fields
            if (!Schema::hasColumn('users', 'banned_at')) {
                $table->timestamp('banned_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('users', 'banned_reason')) {
                $table->text('banned_reason')->nullable()->after('banned_at');
            }
            
            // Add suspension tracking fields
            if (!Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('banned_reason');
            }
            
            if (!Schema::hasColumn('users', 'suspended_reason')) {
                $table->text('suspended_reason')->nullable()->after('suspended_at');
            }
            
            // Add profile image field
            if (!Schema::hasColumn('users', 'profile_image_id')) {
                $table->foreignId('profile_image_id')->nullable()->after('account_type')->constrained('media')->onDelete('set null');
            }
            
            // Add index on status for performance
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropForeign(['profile_image_id']);
            $table->dropColumn([
                'status',
                'banned_at',
                'banned_reason',
                'suspended_at',
                'suspended_reason',
                'profile_image_id',
            ]);
        });
    }
};
