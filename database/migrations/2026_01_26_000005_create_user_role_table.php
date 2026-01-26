<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserRoleTable extends Migration
{
    public function up()
    {
        Schema::create('user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamp('assigned_at')->nullable();
            $table->primary(['user_id', 'role_id']);
            $table->index('role_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_role');
    }
}
