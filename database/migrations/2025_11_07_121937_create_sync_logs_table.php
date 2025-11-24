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
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 50)->nullable();
            $table->string('environment', 20)->default('production');
            $table->string('operation');
            $table->string('direction', 20);
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('request_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->string('endpoint')->nullable();
            $table->unsignedInteger('response_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['channel_connection_id', 'entity_type', 'status'], 'sync_logs_idx');
            $table->index(['channel', 'operation', 'status']);
            $table->index(['request_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
