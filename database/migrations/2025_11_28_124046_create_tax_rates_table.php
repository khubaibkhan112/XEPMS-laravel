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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('name'); // VAT, GST, Service Tax, City Tax, etc.
            $table->string('code')->nullable(); // VAT, GST, TAX1, etc.
            $table->decimal('rate', 5, 2); // Percentage rate (e.g., 20.00 for 20%)
            $table->string('tax_type')->default('percentage'); // percentage, fixed_amount
            $table->decimal('fixed_amount', 10, 2)->nullable(); // For fixed amount taxes
            $table->string('calculation_type')->default('inclusive'); // inclusive (included in price), exclusive (added on top)
            $table->boolean('is_compound')->default(false); // Compound tax (calculated on subtotal + other taxes)
            $table->unsignedTinyInteger('priority')->default(0); // Order of application
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->json('conditions')->nullable(); // Conditions like room_type_id, minimum_amount, etc.
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'is_active']);
            $table->index(['valid_from', 'valid_to']);

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
