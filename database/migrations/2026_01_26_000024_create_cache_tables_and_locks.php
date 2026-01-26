<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheTablesAndLocks extends Migration
{
    public function up()
    {
        if (Schema::hasTable('cache') || Schema::hasTable('cache_locks')) {
            return;
        }

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->unsignedInteger('expiration')->nullable();
            $table->index('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->unsignedInteger('expiration')->nullable();
            $table->index('expiration');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
}
