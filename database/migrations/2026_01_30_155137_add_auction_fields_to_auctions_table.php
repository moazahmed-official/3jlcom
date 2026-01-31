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
        Schema::table('auctions', function (Blueprint $table) {
            // Reserve price - minimum acceptable winning bid
            $table->decimal('reserve_price', 12, 2)->nullable()->after('last_price');
            
            // Minimum bid increment - bid must be at least this much higher than last_price
            $table->decimal('minimum_bid_increment', 12, 2)->default(100)->after('reserve_price');
            
            // Anti-sniping settings
            $table->integer('anti_snip_window_seconds')->default(300)->after('is_last_price_visible');
            $table->integer('anti_snip_extension_seconds')->default(300)->after('anti_snip_window_seconds');
            
            // Auction status
            $table->enum('status', ['active', 'closed', 'cancelled'])->default('active')->after('anti_snip_extension_seconds');
            
            // Bid count for quick access
            $table->unsignedInteger('bid_count')->default(0)->after('status');
            
            // Updated at timestamp
            $table->timestamp('updated_at')->nullable()->after('created_at');
            
            // Add composite index for scheduled job queries
            $table->index(['status', 'end_time'], 'auctions_status_end_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropIndex('auctions_status_end_time_idx');
            $table->dropColumn([
                'reserve_price',
                'minimum_bid_increment',
                'anti_snip_window_seconds',
                'anti_snip_extension_seconds',
                'status',
                'bid_count',
                'updated_at',
            ]);
        });
    }
};
