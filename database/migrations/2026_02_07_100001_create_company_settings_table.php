<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('type'); // contact, social_media, app_link
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default company settings
        DB::table('company_settings')->insert([
            // Contact Information
            [
                'key' => 'phone',
                'value' => '',
                'is_active' => true,
                'type' => 'contact',
                'description' => 'Company phone number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email',
                'value' => '',
                'is_active' => true,
                'type' => 'contact',
                'description' => 'Company email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'location',
                'value' => '',
                'is_active' => true,
                'type' => 'contact',
                'description' => 'Company physical location/address',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Social Media Links
            [
                'key' => 'facebook_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'Facebook page URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'instagram_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'Instagram profile URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'twitter_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'Twitter/X profile URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'youtube_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'YouTube channel URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'telegram_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'Telegram channel/group URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'WhatsApp contact link',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'tiktok_link',
                'value' => '',
                'is_active' => true,
                'type' => 'social_media',
                'description' => 'TikTok profile URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // App Links
            [
                'key' => 'android_app_link',
                'value' => '',
                'is_active' => true,
                'type' => 'app_link',
                'description' => 'Android app download link (Google Play Store)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ios_app_link',
                'value' => '',
                'is_active' => true,
                'type' => 'app_link',
                'description' => 'iOS app download link (Apple App Store)',
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
        Schema::dropIfExists('company_settings');
    }
};
