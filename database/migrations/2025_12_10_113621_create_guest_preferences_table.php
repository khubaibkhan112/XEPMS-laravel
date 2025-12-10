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
        Schema::create('guest_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guest_id');
            $table->unsignedBigInteger('property_id')->nullable(); // Null = global preference
            $table->string('preference_type'); // room_preference, amenity, dietary, special_request, etc.
            $table->string('preference_key'); // e.g., 'floor_level', 'room_view', 'bed_type', 'dietary_restrictions'
            $table->string('preference_value')->nullable(); // e.g., 'high_floor', 'sea_view', 'king_bed', 'vegetarian'
            $table->text('notes')->nullable();
            $table->integer('priority')->default(0); // Higher priority preferences take precedence
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['guest_id', 'property_id']);
            $table->index(['preference_type', 'preference_key']);

            $table->foreign('guest_id')->references('id')->on('guests')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_preferences');
    }
};
