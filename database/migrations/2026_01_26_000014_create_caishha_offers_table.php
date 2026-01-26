<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaishhaOffersTable extends Migration
{
    public function up()
    {
        Schema::create('caishha_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('price', 12, 2);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('ad_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caishha_offers');
    }
}
