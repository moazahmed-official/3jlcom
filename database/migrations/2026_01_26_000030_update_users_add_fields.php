<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAddFields extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country_id')) {
                $table->unsignedInteger('country_id')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'city_id')) {
                $table->unsignedInteger('city_id')->nullable()->after('country_id');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('city_id');
            }
            if (! Schema::hasColumn('users', 'account_type')) {
                $table->string('account_type', 50)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'profile_image_id')) {
                $table->unsignedBigInteger('profile_image_id')->nullable()->after('account_type');
            }
            if (! Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('profile_image_id');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_verified')) {
                $table->dropColumn('is_verified');
            }
            if (Schema::hasColumn('users', 'profile_image_id')) {
                $table->dropColumn('profile_image_id');
            }
            if (Schema::hasColumn('users', 'account_type')) {
                $table->dropColumn('account_type');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'city_id')) {
                $table->dropColumn('city_id');
            }
            if (Schema::hasColumn('users', 'country_id')) {
                $table->dropColumn('country_id');
            }
        });
    }
}
