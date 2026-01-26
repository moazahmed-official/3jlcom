<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinditRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('findit_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->unsignedInteger('model_id')->nullable();
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->smallInteger('min_year')->unsigned()->nullable();
            $table->smallInteger('max_year')->unsigned()->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->timestamps();

            $table->index('requester_id');
            $table->index(['brand_id','model_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('findit_requests');
    }
}
