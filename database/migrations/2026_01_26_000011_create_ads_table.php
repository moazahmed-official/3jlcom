<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTable extends Migration
{
    public function up()
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['normal','unique','caishha','auction'])->default('normal');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->smallInteger('year')->unsigned()->nullable();
            $table->decimal('price_cash', 12, 2)->nullable();
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->string('banner_color', 30)->nullable();
            $table->boolean('is_verified_ad')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->enum('status', ['draft','pending','published','expired','removed'])->default('pending');
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('status');
            $table->index(['brand_id','model_id']);
            $table->index('price_cash');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ads');
    }
}
