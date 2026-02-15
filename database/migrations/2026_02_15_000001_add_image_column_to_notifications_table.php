<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && !Schema::hasColumn('notifications', 'image')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('image')->nullable()->after('data');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'image')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
