<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_upgrade_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('requested_unique_type_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('user_message')->nullable()->comment('Message from user explaining the request');
            $table->text('admin_message')->nullable()->comment('Admin response message');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Admin user who reviewed');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('requested_unique_type_id')->references('id')->on('unique_ad_type_definitions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index('ad_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_upgrade_requests');
    }
};
