<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->string('path', 1024)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('status', 50)->nullable()->default('processing');
            $table->string('thumbnail_url', 1024)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('related_resource', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();

            $table->index(['related_resource', 'related_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('media');
    }
}
