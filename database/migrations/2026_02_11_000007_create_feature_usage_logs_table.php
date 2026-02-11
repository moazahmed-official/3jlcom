<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create feature_usage_logs table.
     * 
     * Tracks every actionable feature usage by a user:
     * - AI video generation
     * - Auto-BG image editing
     * - Pixblin AI
     * - Facebook push
     * - Carseer API
     * 
     * Credits source can be either:
     * - 'package' (paid plan features)
     * - 'unique_ad_type' (free plan approved upgrade)
     */
    public function up(): void
    {
        Schema::create('feature_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ad_id')->nullable()->comment('The ad this usage relates to');
            $table->string('feature', 50)->comment('Feature identifier: facebook_push, ai_video, auto_bg, pixblin, carseer');
            $table->string('credits_source', 30)->comment('package or unique_ad_type');
            $table->unsignedBigInteger('source_id')->comment('ID of package or unique_ad_type_definition');
            $table->integer('credits_used')->default(1)->comment('Credits consumed by this action');
            $table->json('metadata')->nullable()->comment('Extra info about the usage (result URL, etc.)');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('set null');
            
            // Indexes for fast lookups
            $table->index(['user_id', 'feature']);
            $table->index(['user_id', 'credits_source', 'source_id']);
            $table->index('feature');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_usage_logs');
    }
};
