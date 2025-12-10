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
        Schema::create('guest_booking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('nights');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('pending');
            $table->decimal('rating', 3, 2)->nullable(); // Guest rating out of 5
            $table->text('review')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->json('feedback')->nullable(); // Additional feedback data
            $table->timestamps();

            $table->index(['guest_id', 'check_in']);
            $table->index(['property_id', 'check_in']);
            $table->index(['status', 'payment_status']);
            $table->unique('reservation_id'); // One booking history entry per reservation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_booking_history');
    }
};
