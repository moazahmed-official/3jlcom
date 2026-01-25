<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsAndSliders extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('target', 50);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('image_media_id');
            $table->string('location', 100);
            $table->string('value', 1024)->nullable();
            $table->integer('order')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('image_media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('notifications');
    }
}
