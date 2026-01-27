<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToModelsTable extends Migration
{
    public function up()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name_ar');
        });
    }

    public function down()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
