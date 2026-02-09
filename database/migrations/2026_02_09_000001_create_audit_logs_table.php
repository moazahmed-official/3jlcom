<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates an immutable audit_logs table for compliance and forensics.
     * This table is write-once by design - no updates or deletes allowed.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Actor information - who performed the action
            $table->unsignedBigInteger('actor_id')->nullable()->comment('User ID who performed the action');
            $table->string('actor_name', 100)->nullable()->comment('Name of the actor at the time of action');
            $table->string('actor_role', 50)->nullable()->comment('Primary role of the actor');
            
            // Action details - what was done
            $table->string('action_type', 100)->index()->comment('Type of action performed (e.g., user.created, package.updated)');
            $table->string('resource_type', 100)->index()->comment('Type of resource affected (e.g., User, Package, Ad)');
            $table->string('resource_id', 100)->nullable()->index()->comment('ID of the affected resource');
            
            // Request context
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6 address');
            $table->text('user_agent')->nullable()->comment('User agent string');
            $table->string('correlation_id', 36)->nullable()->index()->comment('UUID to trace requests across system');
            
            // Additional data - stored as JSON for flexibility
            $table->json('details')->nullable()->comment('Additional structured data about the action');
            
            // Severity for filtering critical events
            $table->enum('severity', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                ->default('info')
                ->index()
                ->comment('Log severity level');
            
            // Timestamps
            $table->timestamp('timestamp')->useCurrent()->index()->comment('When the action occurred');
            $table->timestamp('archived_at')->nullable()->comment('When this log was archived/exported');
            
            // Indexes for common queries
            $table->index('actor_id');
            $table->index(['timestamp', 'severity']); // Composite for time-based + severity queries
            $table->index(['resource_type', 'resource_id']); // Composite for resource lookups
            $table->index(['actor_id', 'timestamp']); // Composite for user activity timeline
        });

        // Note: MySQL/MariaDB don't support COMMENT ON TABLE syntax like PostgreSQL
        // Table and column comments are already added inline above
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
