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
        // Add foreign key from check_outs to invoices
        if (Schema::hasTable('check_outs') && Schema::hasTable('invoices')) {
            if (!Schema::hasColumn('check_outs', 'invoice_id')) {
                return;
            }

            try {
                Schema::table('check_outs', function (Blueprint $table) {
                    $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
                });
            } catch (\Exception $e) {
                // Foreign key might already exist
            }

            // Add foreign key from invoices to check_outs
            if (Schema::hasColumn('invoices', 'check_out_id')) {
                try {
                    Schema::table('invoices', function (Blueprint $table) {
                        $table->foreign('check_out_id')->references('id')->on('check_outs')->nullOnDelete();
                    });
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('check_outs')) {
            Schema::table('check_outs', function (Blueprint $table) {
                $table->dropForeign(['invoice_id']);
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['check_out_id']);
            });
        }
    }
};
