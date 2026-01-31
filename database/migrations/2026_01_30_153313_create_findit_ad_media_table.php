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
        Schema::create('findit_request_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('findit_request_id')->constrained('findit_requests')->onDelete('cascade');
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['findit_request_id', 'media_id']);
            $table->index('findit_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('findit_request_media');
    }
};
