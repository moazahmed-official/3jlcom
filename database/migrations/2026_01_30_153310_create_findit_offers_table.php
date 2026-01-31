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
        Schema::create('findit_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('findit_request_id')->constrained('findit_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The seller/dealer
            $table->foreignId('ad_id')->nullable()->constrained('ads')->onDelete('set null'); // Optional linked ad
            $table->decimal('price', 12, 2);
            $table->text('comment')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('findit_request_id');
            $table->index('user_id');
            $table->index('status');
            $table->unique(['findit_request_id', 'user_id']); // One offer per dealer per request
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('findit_offers');
    }
};
