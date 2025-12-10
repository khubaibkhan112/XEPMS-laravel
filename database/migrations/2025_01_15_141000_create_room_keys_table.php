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
        Schema::create('room_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->unsignedBigInteger('check_in_id')->nullable();
            $table->string('key_type')->default('physical'); // physical, electronic, card, code
            $table->string('key_identifier')->nullable(); // Key number, card number, code
            $table->string('key_code')->nullable(); // For electronic keys/codes
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->unsignedBigInteger('returned_to')->nullable();
            $table->string('status')->default('available'); // available, issued, lost, damaged, returned
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional key information
            $table->timestamps();

            $table->index(['property_id', 'room_id', 'status']);
            $table->index(['reservation_id']);
            $table->index(['check_in_id']);
            $table->index(['status']);

            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            $table->foreign('reservation_id')->references('id')->on('reservations')->nullOnDelete();
            $table->foreign('check_in_id')->references('id')->on('check_ins')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('returned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_keys');
    }
};




