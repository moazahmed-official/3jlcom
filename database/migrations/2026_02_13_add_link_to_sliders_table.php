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

        if (! Schema::hasColumn('sliders', 'link')) {
            Schema::table('sliders', function (Blueprint $table) {
                $table->string('link', 2000)->nullable()->after('value');
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

        if (Schema::hasColumn('sliders', 'link')) {
            Schema::table('sliders', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }
    }
};
