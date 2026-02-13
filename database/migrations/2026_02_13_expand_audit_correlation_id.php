<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Expand `correlation_id` length to avoid Data too long errors when
 | backend writes long correlation ids like `ADMIN-...`.
 | Note: `->change()` may require `doctrine/dbal`.
 */
return new class extends Migration {
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('correlation_id', 100)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('correlation_id', 36)->nullable()->change();
        });
    }
};
