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
        // Add rating cache columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('city_id')->index();
            $table->unsignedInteger('reviews_count')->default(0)->after('avg_rating')->index();
        });

        // Add rating cache columns to ads table
        Schema::table('ads', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('status')->index();
            $table->unsignedInteger('reviews_count')->default(0)->after('avg_rating')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['avg_rating']);
            $table->dropIndex(['reviews_count']);
            $table->dropColumn(['avg_rating', 'reviews_count']);
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['avg_rating']);
            $table->dropIndex(['reviews_count']);
            $table->dropColumn(['avg_rating', 'reviews_count']);
        });
    }
};
