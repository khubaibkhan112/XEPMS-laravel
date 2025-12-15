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
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('checked_in_by')->nullable();
            // $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            // $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            // $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamp('expected_check_in_at')->nullable();
            $table->timestamp('actual_check_in_at')->nullable();
            $table->integer('early_check_in_minutes')->nullable(); // Minutes before expected time
            $table->integer('late_check_in_minutes')->nullable(); // Minutes after expected time
            $table->integer('guest_count')->default(1);
            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);
            $table->string('identification_type')->nullable(); // passport, driving_license, id_card
            $table->string('identification_number')->nullable();
            $table->string('identification_issued_by')->nullable();
            $table->date('identification_expiry_date')->nullable();
            $table->string('vehicle_registration')->nullable();
            $table->string('parking_space')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->json('guest_signature')->nullable(); // Store signature data
            $table->json('documents')->nullable(); // Store uploaded documents
            $table->string('status')->default('completed'); // completed, pending, cancelled
            $table->timestamps();

            $table->index(['property_id', 'checked_in_at']);
            $table->index(['reservation_id']);
            $table->index(['room_id', 'checked_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};




