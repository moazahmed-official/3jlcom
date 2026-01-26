<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('reported_by_user_id');
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->string('status', 50)->default('open');
            $table->timestamp('created_at')->nullable();

            $table->index('reported_by_user_id');
            $table->index(['target_type','target_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reports');
    }
}
