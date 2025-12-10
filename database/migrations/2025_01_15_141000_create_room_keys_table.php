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
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('check_in_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key_type')->default('physical'); // physical, electronic, card, code
            $table->string('key_identifier')->nullable(); // Key number, card number, code
            $table->string('key_code')->nullable(); // For electronic keys/codes
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('available'); // available, issued, lost, damaged, returned
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional key information
            $table->timestamps();

            $table->index(['property_id', 'room_id', 'status']);
            $table->index(['reservation_id']);
            $table->index(['check_in_id']);
            $table->index(['status']);
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



