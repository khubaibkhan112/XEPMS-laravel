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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('position')->nullable()->after('phone'); // e.g., Manager, Receptionist, Housekeeping
            $table->string('department')->nullable()->after('position'); // e.g., Front Desk, Housekeeping, Maintenance
            $table->unsignedBigInteger('property_id')->nullable()->after('department'); // Default property assignment
            $table->text('notes')->nullable()->after('property_id');
            $table->boolean('is_active')->default(true)->after('notes');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            
            // Foreign key will be added in a separate migration if needed
            // $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'position',
                'department',
                'property_id',
                'notes',
                'is_active',
                'last_login_at',
                'password_changed_at',
            ]);
        });
    }
};


