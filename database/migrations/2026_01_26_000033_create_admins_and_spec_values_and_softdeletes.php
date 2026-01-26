<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsAndSpecValuesAndSoftDeletes extends Migration
{
    public function up()
    {
        // Admins table
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('mobile_number')->nullable();
                $table->unsignedBigInteger('profile_image')->nullable();
                $table->unsignedTinyInteger('role_level')->default(1);
                $table->timestamps();
            });
        }

        // Specification values (normalized choices)
        if (! Schema::hasTable('specification_values')) {
            Schema::create('specification_values', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('specification_id');
                $table->string('value_key');
                $table->string('value_label')->nullable();
                $table->unsignedBigInteger('image_id')->nullable();
                $table->timestamps();

                $table->index('specification_id');
            });
        }

        // Add soft deletes to several tables if not present
        $tables = ['users', 'ads', 'cars', 'blogs', 'sliders'];
        foreach ($tables as $t) {
            if (Schema::hasTable($t) && ! Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, function (Blueprint $table) use ($t) {
                    $table->softDeletes();
                });
            }
        }

        // Link specification_values.specification_id to specifications if possible
        if (Schema::hasTable('specification_values') && Schema::hasTable('specifications')) {
            try {
                Schema::table('specification_values', function (Blueprint $table) {
                    $table->foreign('specification_id')->references('id')->on('specifications')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        // drop foreign then table
        if (Schema::hasTable('specification_values')) {
            Schema::dropIfExists('specification_values');
        }

        if (Schema::hasTable('admins')) {
            Schema::dropIfExists('admins');
        }

        // remove softDeletes columns
        $tables = ['users', 'ads', 'cars', 'blogs', 'sliders'];
        foreach ($tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'deleted_at')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }
        }
    }
}
