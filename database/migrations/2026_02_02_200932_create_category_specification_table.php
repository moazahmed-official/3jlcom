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
        Schema::create('category_specification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('specification_id')->constrained('specifications')->onDelete('cascade');
            $table->integer('order')->default(0); // For ordering specifications within a category
            $table->timestamps();

            // Ensure a specification is only added once per category
            $table->unique(['category_id', 'specification_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_specification');
    }
};
