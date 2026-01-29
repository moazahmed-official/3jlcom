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
        Schema::table('ads', function (Blueprint $table) {
            if (!Schema::hasColumn('ads', 'contact_count')) {
                $table->unsignedInteger('contact_count')->default(0)->after('views_count');
            }
            if (!Schema::hasColumn('ads', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ads', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('published_at');
            }
            if (!Schema::hasColumn('ads', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('expired_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['contact_count', 'published_at', 'expired_at', 'archived_at']);
        });
    }
};
