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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_type_id')->nullable();
            $table->date('date');
            $table->decimal('rate', 10, 2);
            $table->decimal('base_rate', 10, 2)->nullable(); // Original rate before modifications
            $table->string('currency', 3)->default('GBP');
            $table->string('rate_type')->default('default'); // default, seasonal, weekend, holiday, promotional
            $table->string('rate_plan_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Additional conditions like min_stay, max_stay
            $table->timestamps();

            $table->unique(['property_id', 'room_type_id', 'date'], 'rate_unique');
            $table->index(['property_id', 'date']);
            $table->index(['room_type_id', 'date']);
            $table->index(['date', 'is_active']);

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
