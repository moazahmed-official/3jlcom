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
        if (Schema::hasTable('sliders')) {
            return;
        }
        
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedBigInteger('image_media_id')->nullable();
            $table->string('location', 50)->default('home')->comment('home, category, detail, etc.');
            $table->string('value', 500)->nullable()->comment('Link URL or custom value');
            $table->unsignedInteger('order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('location');
            $table->index('status');
            $table->index('order');
            $table->index(['location', 'status', 'order']);

            // Foreign key
            $table->foreign('image_media_id')
                ->references('id')
                ->on('media')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
