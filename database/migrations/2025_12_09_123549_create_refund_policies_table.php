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
        Schema::create('refund_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('refund_type')->default('percentage'); // percentage, fixed_amount, full
            $table->decimal('refund_percentage', 5, 2)->nullable(); // e.g., 50.00 for 50%
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->integer('days_before_checkin')->nullable(); // Days before check-in to qualify
            $table->integer('days_after_booking')->nullable(); // Days after booking to qualify
            $table->integer('minimum_nights')->nullable(); // Minimum nights to qualify
            $table->boolean('requires_cancellation_reason')->default(false);
            $table->json('allowed_cancellation_reasons')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Lower priority = higher precedence
            $table->timestamps();

            $table->index(['property_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_policies');
    }
};
