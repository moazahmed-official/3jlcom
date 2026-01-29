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
        Schema::table('unique_ads', function (Blueprint $table) {
            $table->string('verification_status')->nullable()->default(null)->after('is_verified_ad');
            $table->timestamp('verification_requested_at')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_requested_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');
            $table->text('verification_rejection_reason')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unique_ads', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'verification_status',
                'verification_requested_at',
                'verified_at',
                'verified_by',
                'verification_rejection_reason'
            ]);
        });
    }
};
