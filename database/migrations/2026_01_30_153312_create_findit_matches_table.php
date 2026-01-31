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
        Schema::create('findit_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('findit_request_id')->constrained('findit_requests')->onDelete('cascade');
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->unsignedTinyInteger('match_score')->default(0); // 0-100 score
            $table->timestamp('notified_at')->nullable();
            $table->boolean('dismissed')->default(false);
            $table->timestamps();

            $table->unique(['findit_request_id', 'ad_id']); // One match record per ad per request
            $table->index('findit_request_id');
            $table->index('match_score');
            $table->index('dismissed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('findit_matches');
    }
};
