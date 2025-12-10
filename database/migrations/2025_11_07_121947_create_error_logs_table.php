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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_connection_id')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('environment', 20)->default('production');
            $table->string('context')->nullable();
            $table->string('severity', 20)->default('error');
            $table->string('error_code')->nullable();
            $table->string('request_id')->nullable();
            $table->string('external_reference')->nullable();
            $table->unsignedInteger('http_status')->nullable();
            $table->text('message');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->text('stack_trace')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->index(['channel_connection_id', 'is_resolved']);
            $table->index(['channel', 'severity', 'occurred_at']);
            $table->index(['request_id']);

            $table->foreign('channel_connection_id')->references('id')->on('channel_connections')->nullOnDelete();
            $table->foreign('reservation_id')->references('id')->on('reservations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
