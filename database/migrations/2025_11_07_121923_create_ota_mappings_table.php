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
        Schema::create('ota_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_connection_id');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->string('channel_identifier')->nullable();
            $table->string('ota_room_id')->nullable();
            $table->string('ota_rate_plan_id')->nullable();
            $table->string('ota_listing_id')->nullable();
            $table->string('ota_product_code')->nullable();
            $table->string('market')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->string('sync_direction', 20)->default('bi_directional');
            $table->string('pricing_model', 30)->nullable();
            $table->decimal('rate_multiplier', 8, 4)->default(1);
            $table->boolean('tax_inclusive')->default(true);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->json('restrictions')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_connection_id', 'ota_room_id', 'ota_rate_plan_id'], 'ota_mapping_unique');
            $table->index(['channel_identifier', 'is_active']);

            $table->foreign('channel_connection_id')->references('id')->on('channel_connections')->cascadeOnDelete();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ota_mappings');
    }
};
