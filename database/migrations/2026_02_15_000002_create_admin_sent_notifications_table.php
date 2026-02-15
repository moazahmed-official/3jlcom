<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_sent_notifications')) {
            Schema::create('admin_sent_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sent_by')->nullable()->index();
                $table->string('title');
                $table->text('body')->nullable();
                $table->json('data')->nullable();
                $table->string('image')->nullable();
                $table->string('target')->nullable(); // user|group|all
                $table->string('target_role')->nullable();
                $table->json('recipients')->nullable(); // array of user ids or null
                $table->integer('recipients_count')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_sent_notifications');
    }
};
