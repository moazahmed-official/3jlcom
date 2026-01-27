<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToBrandsTable extends Migration
{
    public function up()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name_ar');
        });
    }

    public function down()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
