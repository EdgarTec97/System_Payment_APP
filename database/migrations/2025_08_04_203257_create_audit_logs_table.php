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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('method', 10);
            $table->text('url');
            $table->string('route_name')->nullable();
            $table->string('controller_action')->nullable();
            $table->json('request_data')->nullable();
            $table->integer('response_status');
            $table->bigInteger('response_size')->nullable();
            $table->decimal('execution_time', 8, 2)->nullable(); // milliseconds
            $table->bigInteger('memory_usage')->nullable(); // bytes
            $table->string('session_id')->nullable();
            $table->text('referer')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['method', 'response_status']);
            $table->index(['route_name', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

