<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->smallInteger('min_year')->unsigned()->nullable();
            $table->smallInteger('max_year')->unsigned()->nullable();
            $table->integer('min_mileage')->unsigned()->nullable();
            $table->integer('max_mileage')->unsigned()->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->string('transmission', 20)->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->string('body_type', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->string('condition', 20)->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'expired'])->default('draft');
            $table->unsignedInteger('media_count')->default(0);
            $table->unsignedInteger('offers_count')->default(0);
            $table->unsignedInteger('matches_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys (optional - will work without them)
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('set null');

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index(['brand_id', 'model_id']);
            $table->index(['min_price', 'max_price']);
            $table->index(['min_year', 'max_year']);
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findit_requests');
    }
};
