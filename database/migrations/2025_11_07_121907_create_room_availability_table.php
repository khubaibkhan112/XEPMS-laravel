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
        Schema::create('room_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->date('date');
            $table->unsignedInteger('available_count')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('closed_to_arrival')->default(false);
            $table->boolean('closed_to_departure')->default(false);
            $table->unsignedTinyInteger('min_stay')->nullable();
            $table->unsignedTinyInteger('max_stay')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->string('rate_plan_code')->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->string('board_basis')->nullable();
            $table->string('inventory_source')->nullable();
            $table->string('channel_identifier')->nullable();
            $table->unsignedTinyInteger('max_occupancy')->nullable();
            $table->unsignedSmallInteger('min_advance_booking_days')->nullable();
            $table->unsignedSmallInteger('max_advance_booking_days')->nullable();
            $table->json('restrictions')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'room_type_id', 'room_id', 'date'], 'availability_unique');
            $table->index(['property_id', 'date']);
            $table->index(['channel_identifier', 'date']);

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_availability');
    }
};
