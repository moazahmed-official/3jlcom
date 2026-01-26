<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformExtensions extends Migration
{
    public function up()
    {
        // Subscriptions
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->integer('duration_days')->unsigned()->default(0);
                $table->json('features')->nullable();
                $table->json('available_for_roles')->nullable();
                $table->enum('status', ['active','inactive'])->default('active');
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
            });
        }

        // Features
        if (! Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('limits')->nullable();
                $table->json('toggles')->nullable();
                $table->timestamps();
            });
        }

        // Categories
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name_en');
                $table->string('name_ar')->nullable();
                $table->enum('status', ['active','inactive'])->default('active');
                $table->unsignedBigInteger('specs_group_id')->nullable();
                $table->timestamps();
            });
        }

        // Specifications
        if (! Schema::hasTable('specifications')) {
            Schema::create('specifications', function (Blueprint $table) {
                $table->id();
                $table->string('name_en');
                $table->string('name_ar')->nullable();
                $table->enum('type', ['text','number','select','boolean'])->default('text');
                $table->json('values')->nullable();
                $table->unsignedBigInteger('image_id')->nullable();
                $table->timestamps();
            });
        }

        // Saved searches
        if (! Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->json('query_params');
                $table->timestamps();
                $table->index('user_id');
            });
        }

        // Views / stats
        if (! Schema::hasTable('views')) {
            Schema::create('views', function (Blueprint $table) {
                $table->id();
                $table->string('target_type');
                $table->unsignedBigInteger('target_id');
                $table->unsignedBigInteger('count')->default(0);
                $table->timestamp('last_viewed_at')->nullable();
                $table->timestamps();
                $table->index(['target_type','target_id']);
            });
        }

        // Blogs
        if (! Schema::hasTable('blogs')) {
            Schema::create('blogs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('image_id')->nullable();
                $table->longText('body')->nullable();
                $table->enum('status', ['draft','published','archived'])->default('draft');
                $table->timestamps();
            });
        }

        // Sliders
        if (! Schema::hasTable('sliders')) {
            Schema::create('sliders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('image_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('value')->nullable();
                $table->enum('status', ['active','inactive'])->default('active');
                $table->timestamps();
            });
        }

        // Add missing columns to users and ads safely
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'mobile_number')) {
                    $table->string('mobile_number')->nullable()->after('phone');
                }
                if (! Schema::hasColumn('users', 'verification_otp')) {
                    $table->string('verification_otp', 32)->nullable()->after('remember_token');
                }
            });
        }

        if (Schema::hasTable('ads')) {
            Schema::table('ads', function (Blueprint $table) {
                if (! Schema::hasColumn('ads', 'contact_phone')) {
                    $table->string('contact_phone', 50)->nullable()->after('description');
                }
                if (! Schema::hasColumn('ads', 'whatsapp_number')) {
                    $table->string('whatsapp_number', 50)->nullable()->after('contact_phone');
                }
                if (! Schema::hasColumn('ads', 'media_count')) {
                    $table->unsignedInteger('media_count')->default(0)->after('views_count');
                }
                if (! Schema::hasColumn('ads', 'period_days')) {
                    $table->unsignedInteger('period_days')->nullable()->after('media_count');
                }
                if (! Schema::hasColumn('ads', 'car_id')) {
                    $table->unsignedBigInteger('car_id')->nullable()->after('model_id');
                }
            });
        }

        // Add foreign keys for saved_searches and views if possible
        if (Schema::hasTable('saved_searches') && Schema::hasTable('users')) {
            try {
                Schema::table('saved_searches', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // ignore if FK exists or incompatible
            }
        }

        if (Schema::hasTable('ads') && Schema::hasTable('cars')) {
            try {
                Schema::table('ads', function (Blueprint $table) {
                    $table->foreign('car_id')->references('id')->on('cars')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        // Revert additions where safe
        if (Schema::hasTable('sliders')) {
            Schema::dropIfExists('sliders');
        }
        if (Schema::hasTable('blogs')) {
            Schema::dropIfExists('blogs');
        }
        if (Schema::hasTable('views')) {
            Schema::dropIfExists('views');
        }
        if (Schema::hasTable('saved_searches')) {
            Schema::dropIfExists('saved_searches');
        }
        if (Schema::hasTable('specifications')) {
            Schema::dropIfExists('specifications');
        }
        if (Schema::hasTable('categories')) {
            Schema::dropIfExists('categories');
        }
        if (Schema::hasTable('features')) {
            Schema::dropIfExists('features');
        }
        if (Schema::hasTable('subscriptions')) {
            Schema::dropIfExists('subscriptions');
        }

        // Remove columns added to users and ads
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'mobile_number')) {
                    $table->dropColumn('mobile_number');
                }
                if (Schema::hasColumn('users', 'verification_otp')) {
                    $table->dropColumn('verification_otp');
                }
            });
        }

        if (Schema::hasTable('ads')) {
            Schema::table('ads', function (Blueprint $table) {
                if (Schema::hasColumn('ads', 'contact_phone')) {
                    $table->dropColumn('contact_phone');
                }
                if (Schema::hasColumn('ads', 'whatsapp_number')) {
                    $table->dropColumn('whatsapp_number');
                }
                if (Schema::hasColumn('ads', 'media_count')) {
                    $table->dropColumn('media_count');
                }
                if (Schema::hasColumn('ads', 'period_days')) {
                    $table->dropColumn('period_days');
                }
                if (Schema::hasColumn('ads', 'car_id')) {
                    try {
                        $table->dropForeign(['car_id']);
                    } catch (\Exception $e) {
                        // ignore
                    }
                    $table->dropColumn('car_id');
                }
            });
        }
    }
}
