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
        Schema::create('unique_ad_type_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('display_name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0)->comment('Informational price for display');
            $table->integer('priority')->default(500)->comment('Lower number = higher priority');
            $table->boolean('active')->default(true);
            
            // Feature toggles
            $table->boolean('allows_frame')->default(false)->comment('Allow any frame for ad');
            $table->boolean('allows_colored_frame')->default(false)->comment('Allow colored frame');
            $table->boolean('allows_image_frame')->default(false)->comment('Allow image frame');
            $table->boolean('auto_republish_enabled')->default(false)->comment('Enable auto-republish/boost');
            $table->boolean('facebook_push_enabled')->default(false)->comment('Enable Facebook Graph API push');
            $table->boolean('caishha_feature_enabled')->default(false)->comment('Enable Caishha feature for this type');
            
            // Future API integrations (placeholders)
            $table->integer('carseer_api_credits')->nullable()->comment('Carseer API credits allowed');
            $table->integer('auto_bg_credits')->nullable()->comment('Auto background change credits');
            $table->integer('pixblin_credits')->nullable()->comment('Pixblin AI image generation credits');
            
            // Media limits
            $table->integer('max_images')->default(10)->comment('Maximum images per ad');
            $table->integer('max_videos')->default(1)->comment('Maximum videos per ad');
            
            // Custom text features (e.g., "car washing", "home delivery")
            $table->json('custom_features_text')->nullable()->comment('Array of custom text features');
            
            $table->timestamps();
            
            // Indexes
            $table->index('priority');
            $table->index('active');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unique_ad_type_definitions');
    }
};
