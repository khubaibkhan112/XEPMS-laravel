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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 3)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('nationality', 3)->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('id_card_type')->nullable(); // driver_license, national_id, etc.
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->enum('guest_type', ['individual', 'corporate', 'travel_agent', 'group'])->default('individual');
            $table->enum('loyalty_status', ['none', 'bronze', 'silver', 'gold', 'platinum'])->default('none');
            $table->integer('loyalty_points')->default(0);
            $table->boolean('email_verified')->default(false);
            $table->boolean('phone_verified')->default(false);
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('preferred_language', 10)->default('en');
            $table->string('preferred_currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable(); // Custom tags for guest segmentation
            $table->json('custom_fields')->nullable(); // Additional custom fields
            $table->timestamp('last_stay_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['email']);
            $table->index(['phone']);
            $table->index(['first_name', 'last_name']);
            $table->index(['loyalty_status']);
            $table->index(['guest_type']);
            $table->fullText(['first_name', 'last_name', 'email', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
