<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAuthorFromBlogs extends Migration
{
    public function up()
    {
        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                if (Schema::hasColumn('blogs', 'author_id')) {
                    try {
                        $table->dropForeign(['author_id']);
                    } catch (\Exception $e) {
                        // ignore
                    }
                    $table->dropColumn('author_id');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                if (! Schema::hasColumn('blogs', 'author_id')) {
                    $table->unsignedBigInteger('author_id')->nullable()->after('image_id');
                    try {
                        $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            });
        }
    }
}
