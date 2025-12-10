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
        Schema::create('channel_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('name');
            $table->string('channel', 50);
            $table->string('connection_type', 20)->default('two_way');
            $table->boolean('uses_sandbox')->default(false);
            $table->string('api_base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->timestamp('last_attempted_sync_at')->nullable();
            $table->string('timezone')->nullable();
            $table->string('region', 10)->default('UK');
            $table->string('locale', 10)->default('en-GB');
            $table->string('currency', 3)->default('GBP');
            $table->timestamps();

            $table->unique(['property_id', 'channel']);
            $table->index(['channel', 'is_active']);

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_connections');
    }
};
