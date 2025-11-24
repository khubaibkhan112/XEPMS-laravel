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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel_reference')->nullable();
            $table->string('ota_reservation_code')->nullable()->index();
            $table->string('external_id')->nullable();
            $table->string('guest_first_name');
            $table->string('guest_last_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('guest_country_code', 2)->nullable();
            $table->string('guest_city')->nullable();
            $table->string('guest_postal_code', 20)->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('adult_count')->default(1);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->decimal('ota_commission_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->string('status', 30)->default('pending');
            $table->string('payment_status', 30)->default('pending');
            $table->string('source')->default('direct');
            $table->string('locale', 10)->default('en-GB');
            $table->string('market', 50)->default('United Kingdom');
            $table->string('rate_plan_code')->nullable();
            $table->string('board_basis')->nullable();
            $table->boolean('is_primary_guest')->default(true);
            $table->json('guest_details')->nullable();
            $table->json('extras')->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->json('fee_breakdown')->nullable();
            $table->json('pricing_breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->time('expected_arrival_time')->nullable();
            $table->time('expected_departure_time')->nullable();
            $table->boolean('requires_channel_confirmation')->default(false);
            $table->timestamp('channel_confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'check_in']);
            $table->index(['property_id', 'check_out']);
            $table->index(['channel_connection_id', 'status']);
            $table->index(['status', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
