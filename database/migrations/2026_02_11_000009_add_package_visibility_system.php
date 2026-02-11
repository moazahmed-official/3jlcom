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
        Schema::table('packages', function (Blueprint $table) {
            // Visibility type: public, role_based, user_specific
            $table->enum('visibility_type', ['public', 'role_based', 'user_specific'])
                ->default('public')
                ->after('active');
            
            // For role_based: JSON array of allowed roles ['seller', 'showroom', 'marketer']
            $table->json('allowed_roles')->nullable()->after('visibility_type');
            
            // For user_specific: will use a pivot table
            // No column needed here, just a note for reference
            
            $table->index('visibility_type');
        });
        
        // Create pivot table for user-specific package access
        Schema::create('package_user_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['package_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_user_access');
        
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['visibility_type']);
            $table->dropColumn(['visibility_type', 'allowed_roles']);
        });
    }
};
