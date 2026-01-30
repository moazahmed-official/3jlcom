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
        Schema::table('caishha_offers', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('caishha_offers', 'status')) {
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->after('comment');
            }
            if (!Schema::hasColumn('caishha_offers', 'is_visible_to_seller')) {
                $table->boolean('is_visible_to_seller')->default(false)->after('status');
            }
            if (!Schema::hasColumn('caishha_offers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }

            // Add indexes for better query performance
            $table->index('user_id');
            $table->index('status');
            $table->index(['ad_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caishha_offers', function (Blueprint $table) {
            if (Schema::hasColumn('caishha_offers', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('caishha_offers', 'is_visible_to_seller')) {
                $table->dropColumn('is_visible_to_seller');
            }
            if (Schema::hasColumn('caishha_offers', 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            // Drop indexes
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['ad_id', 'status']);
        });
    }
};
