<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdMediaTable extends Migration
{
    public function up()
    {
        Schema::create('ad_media', function (Blueprint $table) {
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('media_id');
            $table->unsignedInteger('position')->nullable();
            $table->boolean('is_banner')->default(false);
            $table->primary(['ad_id', 'media_id']);
            $table->index('media_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ad_media');
    }
}
