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
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
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
