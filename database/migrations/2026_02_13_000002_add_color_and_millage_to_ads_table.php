<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorAndMillageToAdsTable extends Migration
{
    public function up()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('color', 100)->nullable()->after('year');
            $table->decimal('millage', 10, 2)->nullable()->after('color');
        });
    }

    public function down()
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn(['color', 'millage']);
        });
    }
}
