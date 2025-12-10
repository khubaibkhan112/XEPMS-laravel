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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method', 50)->default('stripe'); // stripe, paypal, bank_transfer, cash
            $table->string('payment_type', 50)->default('full'); // full, partial, deposit, refund
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed, refunded, cancelled
            $table->string('gateway', 50)->nullable(); // stripe, paypal
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->string('gateway_payment_intent_id')->nullable(); // Stripe PaymentIntent ID
            $table->string('gateway_charge_id')->nullable(); // Stripe Charge ID
            $table->json('gateway_response')->nullable(); // Full response from gateway
            $table->json('metadata')->nullable(); // Additional metadata
            $table->text('description')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('refund_reason')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'status']);
            $table->index(['property_id', 'status']);
            $table->index(['gateway_transaction_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
