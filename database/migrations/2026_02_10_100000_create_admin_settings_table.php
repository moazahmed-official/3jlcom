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
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string'); // string, boolean, integer, json
            $table->string('group', 100)->default('general'); // general, features, notifications, email
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether setting is visible to public API
            $table->timestamps();

            $table->index(['group']);
            $table->index(['key', 'group']);
        });

        // Insert default settings
        DB::table('admin_settings')->insert([
            // General Settings
            [
                'key' => 'site_name',
                'value' => '3JL Auto Trading Platform',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Website name displayed in header and emails',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'URL or path to site logo',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Enable maintenance mode',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Feature Flags
            [
                'key' => 'enable_auctions',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable auction ads functionality',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_caishha_ads',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable Caishha request-for-offers ads',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_findit_ads',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable FindIt private search requests',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'require_seller_verification',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Require seller verification to post ads',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'enable_reviews',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Enable ad and seller reviews',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Notifications
            [
                'key' => 'email_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Enable email notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sms_notifications',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Enable SMS notifications',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
