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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('check_out_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft'); // draft, sent, paid, overdue, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('additional_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->string('currency', 3)->default('GBP');
            $table->json('line_items')->nullable(); // Breakdown of charges
            $table->json('tax_breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('payment_terms')->nullable(); // e.g., "Net 30"
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reservation_id']);
            $table->index(['property_id', 'invoice_date']);
            $table->index(['status', 'due_date']);
            $table->index(['invoice_number']);

            $table->foreign('reservation_id')->references('id')->on('reservations')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });

        // Add foreign key constraint after check_outs table is created
        if (Schema::hasTable('check_outs')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('check_out_id')->references('id')->on('check_outs')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
