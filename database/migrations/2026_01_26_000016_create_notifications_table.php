<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('target', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index(['target','target_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
