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
        Schema::create('package_unique_ad_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('unique_ad_type_id');
            $table->integer('ads_limit')->nullable()->comment('Null = unlimited, 0 = none, N = limit');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('unique_ad_type_id')->references('id')->on('unique_ad_type_definitions')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['package_id', 'unique_ad_type_id']);
            
            // Indexes
            $table->index('package_id');
            $table->index('unique_ad_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_unique_ad_types');
    }
};
