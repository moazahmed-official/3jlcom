<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a lightweight table that records which admin user has read
     * which audit log entries. This preserves audit log immutability while
     * allowing per-admin read/unread state.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_log_reads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('audit_log_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['audit_log_id', 'user_id']);

            $table->foreign('audit_log_id')->references('id')->on('audit_logs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_log_reads');
    }
}
