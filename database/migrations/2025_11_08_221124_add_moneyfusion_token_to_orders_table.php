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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('moneyfusion_token')->nullable()->after('payment_method');
            $table->string('moneyfusion_payment_url')->nullable()->after('moneyfusion_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['moneyfusion_token', 'moneyfusion_payment_url']);
        });
    }
};
