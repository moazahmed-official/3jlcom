<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This creates the standard Laravel notifications table.
     * The existing 'notifications' table has been renamed to 'legacy_notifications'
     * to preserve any existing data while allowing Laravel's notification system to work.
     */
    public function up(): void
    {
        // Rename the existing custom notifications table if it exists
        if (Schema::hasTable('notifications') && !Schema::hasTable('legacy_notifications')) {
            Schema::rename('notifications', 'legacy_notifications');
        }

        // Drop the old notifications table if it wasn't renamed (fresh install)
        if (Schema::hasTable('notifications')) {
            Schema::dropIfExists('notifications');
        }

        // Create Laravel's standard notifications table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');

        // Restore the legacy table if it exists
        if (Schema::hasTable('legacy_notifications')) {
            Schema::rename('legacy_notifications', 'notifications');
        }
    }
};
