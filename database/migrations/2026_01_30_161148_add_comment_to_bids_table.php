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
        Schema::table('bids', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('price');
            $table->enum('status', ['active', 'withdrawn', 'outbid', 'winning'])->default('active')->after('comment');
            $table->timestamp('withdrawn_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropColumn(['comment', 'status', 'withdrawn_at']);
        });
    }
};
