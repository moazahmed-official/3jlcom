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
            $table->unsignedBigInteger('unique_ad_type_id')->nullable()->after('ad_id');
            $table->boolean('applies_caishha_feature')->default(false)->after('is_auto_republished')
                ->comment('Whether this unique ad uses Caishha feature');
            
            $table->foreign('unique_ad_type_id')
                ->references('id')
                ->on('unique_ad_type_definitions')
                ->onDelete('set null');
                
            $table->index('unique_ad_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unique_ads', function (Blueprint $table) {
            $table->dropForeign(['unique_ad_type_id']);
            $table->dropColumn(['unique_ad_type_id', 'applies_caishha_feature']);
        });
    }
};
