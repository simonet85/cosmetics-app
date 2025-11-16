<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add shipping_cost column as an alias/additional column
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax');

            // Update payment_method enum to include moneyfusion
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('credit_card','paypal','stripe','cash_on_delivery','bank_transfer','moneyfusion') NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_cost');

            // Revert payment_method enum
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('credit_card','paypal','stripe','cash_on_delivery') NULL");
        });
    }
};
