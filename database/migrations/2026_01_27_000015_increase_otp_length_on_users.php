<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = config('database.default');

        // Use raw SQL to avoid requiring doctrine/dbal
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `otp` VARCHAR(191) NULL");
        } else {
            // For sqlite and others, use schema builder where possible
            Schema::table('users', function ($table) {
                $table->string('otp', 191)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = config('database.default');

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `otp` VARCHAR(10) NULL");
        } else {
            Schema::table('users', function ($table) {
                $table->string('otp', 10)->nullable()->change();
            });
        }
    }
};