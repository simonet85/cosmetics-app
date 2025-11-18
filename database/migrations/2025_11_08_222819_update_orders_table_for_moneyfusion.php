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
        // Check if shipping_cost column already exists
        if (!Schema::hasColumn('orders', 'shipping_cost')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax');
            });
        }

        // For SQLite, we don't modify ENUM (it uses CHECK constraints)
        // The payment_method column already accepts text values, so 'moneyfusion' will work
        // For MySQL, update the ENUM to include moneyfusion
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('credit_card','paypal','stripe','cash_on_delivery','bank_transfer','moneyfusion') NULL");
        }
        // For SQLite, no action needed - it stores enums as text with CHECK constraints
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'shipping_cost')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('shipping_cost');
            });
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN payment_method ENUM('credit_card','paypal','stripe','cash_on_delivery') NULL");
        }
    }
};
