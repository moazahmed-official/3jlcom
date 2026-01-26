<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFavoritesTable extends Migration
{
    public function up()
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ad_id');
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id','ad_id']);
            $table->index('ad_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('favorites');
    }
}
