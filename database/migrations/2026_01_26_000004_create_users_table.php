<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->unsignedInteger('country_id')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('account_type', 50)->nullable();
            $table->unsignedBigInteger('profile_image_id')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index('country_id');
            $table->index('city_id');
            $table->index('phone');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
