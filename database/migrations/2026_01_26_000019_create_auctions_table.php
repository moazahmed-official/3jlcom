<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuctionsTable extends Migration
{
    public function up()
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->decimal('start_price', 12, 2)->nullable();
            $table->decimal('last_price', 12, 2)->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->unsignedBigInteger('winner_user_id')->nullable();
            $table->boolean('auto_close')->default(false);
            $table->boolean('is_last_price_visible')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->index('ad_id');
            $table->index('end_time');
        });
    }

    public function down()
    {
        Schema::dropIfExists('auctions');
    }
}
