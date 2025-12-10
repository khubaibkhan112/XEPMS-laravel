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
        Schema::create('rate_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('rule_type'); // weekend, weekday, seasonal, length_of_stay, occupancy, last_minute, advance_booking
            $table->decimal('rate_adjustment', 10, 2)->nullable(); // Fixed amount adjustment
            $table->decimal('rate_multiplier', 5, 2)->nullable(); // Percentage multiplier (1.0 = 100%, 1.1 = 110%)
            $table->decimal('fixed_rate', 10, 2)->nullable(); // Override with fixed rate
            $table->string('adjustment_type')->default('percentage'); // percentage, fixed_amount, fixed_rate
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->json('conditions')->nullable(); // Day of week, month range, etc.
            $table->unsignedTinyInteger('priority')->default(0); // Higher priority rules apply first
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'rule_type', 'is_active']);
            $table->index(['room_type_id', 'rule_type']);
            $table->index(['valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_rules');
    }
};
