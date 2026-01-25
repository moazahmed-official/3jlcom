<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsAndReports extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id');
            $table->tinyInteger('stars');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status', 50)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('reported_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('reviews');
    }
}
