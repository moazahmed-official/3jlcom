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
        if (! Schema::hasTable('sliders')) {
            return;
        }

        if (! Schema::hasColumn('sliders', 'caption')) {
            Schema::table('sliders', function (Blueprint $table) {
                // Place caption after link if available, otherwise after value
                if (Schema::hasColumn('sliders', 'link')) {
                    $table->string('caption', 500)->nullable()->after('link');
                } else {
                    $table->string('caption', 500)->nullable()->after('value');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('sliders')) {
            return;
        }

        if (Schema::hasColumn('sliders', 'caption')) {
            Schema::table('sliders', function (Blueprint $table) {
                $table->dropColumn('caption');
            });
        }
    }
};
