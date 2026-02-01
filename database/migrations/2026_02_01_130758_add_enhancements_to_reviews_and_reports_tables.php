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
        // Enhance reviews table - only add assigned_to and rating cache will be in separate migration
        Schema::table('reviews', function (Blueprint $table) {
            // Add updated_at column if it doesn't exist
            if (!Schema::hasColumn('reviews', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        // Enhance reports table
        Schema::table('reports', function (Blueprint $table) {
            // Add updated_at column if it doesn't exist
            if (!Schema::hasColumn('reports', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
            
            // Add assigned_to column for moderator assignment if it doesn't exist
            if (!Schema::hasColumn('reports', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse reviews table changes
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        // Reverse reports table changes
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
            if (Schema::hasColumn('reports', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
