<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('findit_requests', function (Blueprint $table) {
            // Add condition_rating as percentage (0-100)
            $table->tinyInteger('condition_rating')->unsigned()->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('findit_requests', function (Blueprint $table) {
            $table->dropColumn('condition_rating');
        });
    }
};
