<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBidsTable extends Migration
{
    public function up()
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auction_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('price', 12, 2);
            $table->timestamp('created_at')->nullable();

            $table->index('auction_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bids');
    }
}
