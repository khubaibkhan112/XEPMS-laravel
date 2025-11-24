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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('original_rate', 10, 2)->nullable();
            $table->unsignedTinyInteger('adult_count')->default(1);
            $table->unsignedTinyInteger('child_count')->default(0);
            $table->json('pricing_breakdown')->nullable();
            $table->json('taxes')->nullable();
            $table->json('fees')->nullable();
            $table->string('status')->default('booked');
            $table->string('currency', 3)->default('GBP');
            $table->string('rate_plan_code')->nullable();
            $table->string('board_basis')->nullable();
            $table->string('channel_identifier')->nullable();
            $table->boolean('is_derived_rate')->default(false);
            $table->timestamps();

            $table->unique(['reservation_id', 'date']);
            $table->index(['channel_identifier', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
