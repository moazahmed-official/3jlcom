<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinditAndOffers extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('findit_requests')) {
            Schema::create('findit_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('title');
                $table->text('description');
                $table->decimal('desired_price', 12, 2)->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            try {
                if (Schema::hasTable('users')) {
                    Schema::table('findit_requests', function (Blueprint $table) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    });
                }
            } catch (\Exception $e) {}

            try {
                if (Schema::hasTable('cities')) {
                    Schema::table('findit_requests', function (Blueprint $table) {
                        $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {}

            try {
                if (Schema::hasTable('countries')) {
                    Schema::table('findit_requests', function (Blueprint $table) {
                        $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {}

            try {
                if (Schema::hasTable('categories')) {
                    Schema::table('findit_requests', function (Blueprint $table) {
                        $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {}
        }

        Schema::create('offers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('resource_type', 100);
            $table->unsignedBigInteger('resource_id');
            $table->decimal('price', 12, 2);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['resource_type', 'resource_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('offers');
        Schema::dropIfExists('findit_requests');
    }
}
