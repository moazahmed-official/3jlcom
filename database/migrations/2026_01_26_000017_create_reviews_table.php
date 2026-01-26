<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->unsignedTinyInteger('stars')->default(5);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->unsignedBigInteger('ad_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('seller_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
