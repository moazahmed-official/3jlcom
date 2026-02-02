<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores granular package features including:
     * - Ad type permissions and limits (normal, unique, caishha, findit, auction)
     * - Role upgrade capabilities (seller, marketer)
     * - Ad-level features (facebook push, auto republish, banner, background color)
     */
    public function up(): void
    {
        Schema::create('package_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            
            // ========================================
            // AD TYPE PERMISSIONS & LIMITS
            // ========================================
            
            // Normal Ads
            $table->boolean('normal_ads_allowed')->default(true);
            $table->unsignedInteger('normal_ads_limit')->nullable()->comment('NULL = unlimited');
            
            // Unique/Featured Ads
            $table->boolean('unique_ads_allowed')->default(false);
            $table->unsignedInteger('unique_ads_limit')->nullable()->comment('NULL = unlimited');
            
            // Caishha Ads (offers-first)
            $table->boolean('caishha_ads_allowed')->default(false);
            $table->unsignedInteger('caishha_ads_limit')->nullable()->comment('NULL = unlimited');
            
            // FindIt Ads (request-by-spec)
            $table->boolean('findit_ads_allowed')->default(false);
            $table->unsignedInteger('findit_ads_limit')->nullable()->comment('NULL = unlimited');
            
            // Auction Ads
            $table->boolean('auction_ads_allowed')->default(false);
            $table->unsignedInteger('auction_ads_limit')->nullable()->comment('NULL = unlimited');
            
            // ========================================
            // ROLE/USER UPGRADE FEATURES
            // ========================================
            
            // Seller/Dealer features
            $table->boolean('grants_seller_status')->default(false)->comment('Upgrades user to seller role');
            $table->boolean('auto_verify_seller')->default(false)->comment('Auto-verify as seller when package assigned');
            
            // Marketer features
            $table->boolean('grants_marketer_status')->default(false)->comment('Upgrades user to marketer role');
            
            // Verified badge
            $table->boolean('grants_verified_badge')->default(false)->comment('User gets verified badge');
            
            // ========================================
            // AD-LEVEL CAPABILITIES
            // ========================================
            
            // Facebook integration
            $table->boolean('can_push_to_facebook')->default(false)->comment('Ads can be pushed to Facebook');
            
            // Auto republish for unique ads
            $table->boolean('can_auto_republish')->default(false)->comment('Unique ads can auto-republish');
            
            // Visual enhancements
            $table->boolean('can_use_banner')->default(false)->comment('Ads can have banner images');
            $table->boolean('can_use_background_color')->default(false)->comment('Ads can have custom background color');
            
            // Priority/Featured placement
            $table->boolean('can_feature_ads')->default(false)->comment('Can mark ads as featured');
            $table->unsignedInteger('featured_ads_limit')->nullable()->comment('Max featured ads, NULL = unlimited');
            
            // ========================================
            // ADDITIONAL CAPABILITIES
            // ========================================
            
            // Support & Analytics
            $table->boolean('priority_support')->default(false);
            $table->boolean('advanced_analytics')->default(false);
            
            // Bulk operations
            $table->boolean('bulk_upload_allowed')->default(false);
            $table->unsignedInteger('bulk_upload_limit')->nullable()->comment('Max items per bulk upload');
            
            // Media limits
            $table->unsignedInteger('images_per_ad_limit')->nullable()->default(10)->comment('Max images per ad');
            $table->unsignedInteger('videos_per_ad_limit')->nullable()->default(1)->comment('Max videos per ad');
            
            // Contact visibility
            $table->boolean('show_contact_immediately')->default(false)->comment('Show contact without click tracking');
            
            // Ad duration
            $table->unsignedInteger('ad_duration_days')->nullable()->default(30)->comment('Default ad duration');
            $table->unsignedInteger('max_ad_duration_days')->nullable()->default(90)->comment('Max ad duration');
            
            $table->timestamps();
            
            // Each package has exactly one feature set
            $table->unique('package_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_features');
    }
};
