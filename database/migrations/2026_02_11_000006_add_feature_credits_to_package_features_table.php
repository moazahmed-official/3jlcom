<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add actionable feature credits and Caishha feature to package_features.
     * These mirror the same fields in unique_ad_type_definitions so both
     * free-plan (type-based) and paid-plan (package-based) users get
     * the same set of features.
     */
    public function up(): void
    {
        Schema::table('package_features', function (Blueprint $table) {
            // ========================================
            // ACTIONABLE FEATURE CREDITS
            // ========================================
            
            // Frame features (already have can_use_banner / can_use_background_color)
            $table->boolean('allows_image_frame')->default(false)
                ->after('can_use_background_color')
                ->comment('Ads can have image frames');
            
            // Caishha as a feature (not just an ad type)
            $table->boolean('caishha_feature_enabled')->default(false)
                ->after('allows_image_frame')
                ->comment('Enable Caishha seller-first feature on any ad');
            
            // AI / API credits
            $table->unsignedInteger('facebook_push_limit')->nullable()
                ->after('can_push_to_facebook')
                ->comment('Max Facebook pushes, NULL = unlimited when enabled');
            
            $table->unsignedInteger('carseer_api_credits')->nullable()
                ->after('caishha_feature_enabled')
                ->comment('Carseer API credits, NULL = not available');
            
            $table->unsignedInteger('auto_bg_credits')->nullable()
                ->after('carseer_api_credits')
                ->comment('AI auto-background change credits');
            
            $table->unsignedInteger('pixblin_credits')->nullable()
                ->after('auto_bg_credits')
                ->comment('Pixblin AI image generation/editing credits');
            
            $table->unsignedInteger('ai_video_credits')->nullable()
                ->after('pixblin_credits')
                ->comment('AI video generation credits');
            
            // Custom text features
            $table->json('custom_features_text')->nullable()
                ->after('ai_video_credits')
                ->comment('Array of custom text features (car washing, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_features', function (Blueprint $table) {
            $table->dropColumn([
                'allows_image_frame',
                'caishha_feature_enabled',
                'facebook_push_limit',
                'carseer_api_credits',
                'auto_bg_credits',
                'pixblin_credits',
                'ai_video_credits',
                'custom_features_text',
            ]);
        });
    }
};
