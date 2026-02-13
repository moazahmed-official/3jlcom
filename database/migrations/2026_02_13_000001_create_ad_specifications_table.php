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
        Schema::create('ad_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->foreignId('specification_id')->constrained('specifications')->onDelete('cascade');
            $table->text('value')->nullable(); // Store the specification value (text, number, or JSON for multi-select)
            $table->timestamps();

            // Prevent duplicate specification entries per ad
            $table->unique(['ad_id', 'specification_id']);

            $table->index('ad_id');
            $table->index('specification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_specifications');
    }
};
