<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTables extends Migration
{
    public function up()
    {
        Schema::create('normal_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->integer('year')->nullable();
            $table->decimal('price_cash', 12, 2)->nullable();
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->string('status', 50)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('set null');
            $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });

        Schema::create('unique_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->string('banner_color', 50)->nullable();
            $table->boolean('is_verified_ad')->default(false);
            $table->decimal('price_cash', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });

        Schema::create('caishha_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->integer('offers_window_period_days')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('caishha_ads');
        Schema::dropIfExists('unique_ads');
        Schema::dropIfExists('normal_ads');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('normal_ads')) {
            Schema::create('normal_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->integer('year')->nullable();
            $table->decimal('price_cash', 12, 2)->nullable();
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->string('status', 50)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('set null');
            $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            });

            // add foreign keys if referenced tables exist
            if (Schema::hasTable('users')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // ignore FK creation errors if constraint already exists
                }
            }
            if (Schema::hasTable('brands')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('models')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('model_id')->references('id')->on('models')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('media')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('categories')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('cities')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('countries')) {
                try {
                    Schema::table('normal_ads', function (Blueprint $table) {
                        $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
        
        }

        if (!Schema::hasTable('unique_ads')) {
            Schema::create('unique_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('banner_image_id')->nullable();
            $table->string('banner_color', 50)->nullable();
            $table->boolean('is_verified_ad')->default(false);
            $table->decimal('price_cash', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            });

            if (Schema::hasTable('users')) {
                try {
                    Schema::table('unique_ads', function (Blueprint $table) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('media')) {
                try {
                    Schema::table('unique_ads', function (Blueprint $table) {
                        $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('categories')) {
                try {
                    Schema::table('unique_ads', function (Blueprint $table) {
                        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('cities')) {
                try {
                    Schema::table('unique_ads', function (Blueprint $table) {
                        $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('countries')) {
                try {
                    Schema::table('unique_ads', function (Blueprint $table) {
                        $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
        }

        if (!Schema::hasTable('caishha_ads')) {
            Schema::create('caishha_ads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->integer('offers_window_period_days')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
            });

            if (Schema::hasTable('users')) {
                try {
                    Schema::table('caishha_ads', function (Blueprint $table) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('cities')) {
                try {
                    Schema::table('caishha_ads', function (Blueprint $table) {
                        $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
            if (Schema::hasTable('countries')) {
                try {
                    Schema::table('caishha_ads', function (Blueprint $table) {
                        $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');
                    });
                } catch (\Exception $e) {}
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('caishha_ads');
        Schema::dropIfExists('unique_ads');
        Schema::dropIfExists('normal_ads');
    }
}
