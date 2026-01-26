<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOffersTable extends Migration
{
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('price', 12, 2);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('ad_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('offers');
    }
}
