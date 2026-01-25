<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('phone', 32)->unique();
                $table->string('password')->nullable();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->string('account_type', 50)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->unsignedBigInteger('profile_image_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });

            // Add foreign keys if target tables exist
            if (Schema::hasTable('media')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('profile_image_id')->references('id')->on('media')->onDelete('set null');
                });
            }

            if (Schema::hasTable('countries')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                });
            }

            if (Schema::hasTable('cities')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                });
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
