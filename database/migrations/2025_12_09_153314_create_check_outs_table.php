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
        if (Schema::hasTable('check_outs')) {
            Schema::dropIfExists('check_outs');
        }

        Schema::create('check_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('checked_out_by')->nullable();
            $table->timestamp('expected_check_out_at')->nullable();
            $table->timestamp('actual_check_out_at')->nullable();
            $table->integer('early_check_out_minutes')->nullable();
            $table->integer('late_check_out_minutes')->nullable();
            $table->integer('guest_count')->nullable();
            $table->text('departure_notes')->nullable();
            $table->json('room_condition')->nullable(); // Room inspection notes
            $table->json('key_return')->nullable(); // Key return details
            $table->json('incidentals')->nullable(); // Additional charges during stay
            $table->decimal('additional_charges', 10, 2)->default(0);
            $table->decimal('damages', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending'); // pending, paid, partial
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamps();

            $table->index(['property_id', 'actual_check_out_at']);
            $table->index(['reservation_id']);
            $table->index(['status', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_outs');
    }
};
