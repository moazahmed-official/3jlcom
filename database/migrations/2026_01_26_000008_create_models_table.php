<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelsTable extends Migration
{
    public function up()
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->smallInteger('year_from')->unsigned()->nullable();
            $table->smallInteger('year_to')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('brand_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('models');
    }
}
