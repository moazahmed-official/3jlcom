<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default "Classic Unique" ad type for existing unique ads
        $defaultTypeId = DB::table('unique_ad_type_definitions')->insertGetId([
            'name' => 'Classic Unique',
            'slug' => 'classic-unique',
            'display_name' => 'Classic Unique Ad',
            'description' => 'Traditional unique ad with standard features',
            'price' => 0,
            'priority' => 500,
            'active' => true,
            
            // Enable all current unique ad features
            'allows_frame' => true,
            'allows_colored_frame' => true,
            'allows_image_frame' => true,
            'auto_republish_enabled' => true,
            'facebook_push_enabled' => true,
            'caishha_feature_enabled' => false,
            
            'max_images' => 10,
            'max_videos' => 1,
            
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update all existing unique_ads records to reference this default type
        DB::table('unique_ads')
            ->whereNull('unique_ad_type_id')
            ->update(['unique_ad_type_id' => $defaultTypeId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the default type (will set unique_ad_type_id to null due to FK)
        DB::table('unique_ad_type_definitions')
            ->where('slug', 'classic-unique')
            ->delete();
    }
};
