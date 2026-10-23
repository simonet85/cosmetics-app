<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock is reserved when an order is placed. This marks the orders whose stock has been given back
     * (cancelled orders), so releasing and re-reserving can each happen only once.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_released_at')->nullable()->after('status');
        });

        // The old cancel paths already restored the stock of every order that is cancelled today.
        DB::table('orders')->where('status', 'cancelled')->update(['stock_released_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_released_at');
        });
    }
};
