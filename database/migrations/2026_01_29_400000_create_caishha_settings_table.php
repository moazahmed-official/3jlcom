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
        Schema::create('caishha_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('caishha_settings')->insert([
            [
                'key' => 'default_dealer_window_seconds',
                'value' => '129600', // 36 hours
                'type' => 'integer',
                'description' => 'Default dealer-exclusive window period in seconds (36 hours = 129600)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'default_visibility_period_seconds',
                'value' => '129600', // 36 hours
                'type' => 'integer',
                'description' => 'Default seller visibility period in seconds (36 hours = 129600)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'min_dealer_window_seconds',
                'value' => '3600', // 1 hour
                'type' => 'integer',
                'description' => 'Minimum allowed dealer window period in seconds',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_dealer_window_seconds',
                'value' => '604800', // 7 days
                'type' => 'integer',
                'description' => 'Maximum allowed dealer window period in seconds',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'min_visibility_period_seconds',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Minimum allowed visibility period in seconds (0 = immediate)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_visibility_period_seconds',
                'value' => '604800', // 7 days
                'type' => 'integer',
                'description' => 'Maximum allowed visibility period in seconds',
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
        Schema::dropIfExists('caishha_settings');
    }
};