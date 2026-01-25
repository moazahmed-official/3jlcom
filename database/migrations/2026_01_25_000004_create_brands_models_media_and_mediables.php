<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandsModelsMediaAndMediables extends Migration
{
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->timestamps();
        });

        Schema::create('models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('brand_id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->integer('year_from')->nullable();
            $table->integer('year_to')->nullable();
            $table->timestamps();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('url', 1024);
            $table->string('thumbnail_url', 1024)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('related_resource', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mediables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('media_id');
            $table->string('mediable_type');
            $table->unsignedBigInteger('mediable_id');
            $table->integer('order')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
            $table->index(['mediable_type', 'mediable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
        Schema::dropIfExists('models');
        Schema::dropIfExists('brands');
    }
}
