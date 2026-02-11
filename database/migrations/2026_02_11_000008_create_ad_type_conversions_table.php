<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create ad_type_conversions table.
     * 
     * Tracks when a user converts an ad from one type to another.
     * Both type counters are deducted and the conversion is not reversible.
     */
    public function up(): void
    {
        Schema::create('ad_type_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('user_id');
            $table->string('from_type', 20)->comment('Original ad type');
            $table->string('to_type', 20)->comment('New ad type');
            $table->unsignedBigInteger('unique_ad_type_id')->nullable()
                ->comment('If converting to unique, which type definition');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('unique_ad_type_id')
                ->references('id')->on('unique_ad_type_definitions')
                ->onDelete('set null');
            
            // Indexes
            $table->index('ad_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_type_conversions');
    }
};
