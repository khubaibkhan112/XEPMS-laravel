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
        Schema::create('rate_inventory_sync', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_connection_id');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->date('date')->nullable();
            $table->string('channel', 50)->nullable();
            $table->string('environment', 20)->default('production');
            $table->string('operation');
            $table->string('sync_type');
            $table->string('direction', 20)->default('push');
            $table->string('status', 30)->default('pending');
            $table->string('currency', 3)->default('GBP');
            $table->string('rate_plan_code')->nullable();
            $table->string('inventory_source')->nullable();
            $table->string('payload_hash')->nullable();
            $table->string('request_id')->nullable();
            $table->unsignedSmallInteger('priority')->default(3);
            $table->unsignedTinyInteger('retry_limit')->default(3);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('response_code')->nullable();
            $table->text('response_message')->nullable();
            $table->string('last_error_code')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['channel_connection_id', 'sync_type', 'status'], 'rate_sync_idx');
            $table->index(['channel', 'operation', 'status']);
            $table->index(['scheduled_at', 'status']);

            $table->foreign('channel_connection_id')->references('id')->on('channel_connections')->cascadeOnDelete();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_inventory_sync');
    }
};
