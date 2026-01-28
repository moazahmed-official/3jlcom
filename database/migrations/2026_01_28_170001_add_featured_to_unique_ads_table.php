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
        Schema::table('unique_ads', function (Blueprint $table) {
            if (!Schema::hasColumn('unique_ads', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_verified_ad');
            }
            if (!Schema::hasColumn('unique_ads', 'featured_at')) {
                $table->timestamp('featured_at')->nullable()->after('is_featured');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unique_ads', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'featured_at']);
        });
    }
};
