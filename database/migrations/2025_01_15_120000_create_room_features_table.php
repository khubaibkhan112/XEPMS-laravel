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
        Schema::create('room_features', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_type_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('addon'); // 'addon', 'extra_bed', 'amenity', 'service'
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('pricing_type')->default('per_night'); // 'per_night', 'per_stay', 'per_person', 'per_person_per_night'
            $table->string('currency', 3)->default('GBP');
            $table->unsignedTinyInteger('max_quantity')->nullable(); // null = unlimited
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('conditions')->nullable(); // e.g., min_stay, applicable_dates, etc.
            $table->timestamps();

            $table->index(['property_id', 'room_type_id']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_features');
    }
};




