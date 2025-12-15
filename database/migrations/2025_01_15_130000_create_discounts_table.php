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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('property_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('percentage'); // 'percentage', 'fixed_amount', 'free_night'
            $table->decimal('discount_value', 10, 2)->default(0); // percentage or fixed amount
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // max discount for percentage type
            $table->decimal('min_purchase_amount', 10, 2)->nullable(); // minimum order value
            $table->string('currency', 3)->default('GBP');
            // $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('min_stay')->nullable();
            $table->unsignedTinyInteger('max_stay')->nullable();
            $table->unsignedTinyInteger('min_occupancy')->nullable();
            $table->unsignedTinyInteger('max_occupancy')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); // total usage limit
            $table->unsignedInteger('usage_count')->default(0); // current usage count
            $table->unsignedInteger('usage_limit_per_user')->nullable(); // per user limit
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // visible to customers
            $table->json('applicable_days')->nullable(); // days of week [0-6]
            $table->json('excluded_dates')->nullable(); // dates when discount doesn't apply
            $table->json('included_dates')->nullable(); // dates when discount applies
            $table->string('loyalty_tier')->nullable(); // for loyalty programs
            $table->integer('loyalty_points_required')->nullable(); // points needed to redeem
            $table->json('conditions')->nullable(); // additional conditions
            $table->timestamps();

            $table->index(['property_id', 'code']);
            $table->index(['property_id', 'is_active', 'start_date', 'end_date']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};





