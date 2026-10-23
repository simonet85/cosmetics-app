<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Orders created by the old checkout hold their addresses double-encoded (a JSON string that
     * contains JSON). Rewrite them as plain JSON objects. Rows already in the right shape are left alone.
     */
    public function up(): void
    {
        DB::table('orders')->orderBy('id')->each(function ($order) {
            $updates = [];

            foreach (['shipping_address', 'billing_address'] as $column) {
                $decoded = json_decode((string) $order->$column, true);

                if (is_string($decoded) && is_array($inner = json_decode($decoded, true))) {
                    $updates[$column] = json_encode($inner, JSON_UNESCAPED_UNICODE);
                }
            }

            if ($updates) {
                DB::table('orders')->where('id', $order->id)->update($updates);
            }
        });
    }

    /**
     * Not reversible: the plain JSON form is read correctly by the old and the new code paths
     * only through AddressCast, and the double encoding was a bug, not a format worth restoring.
     */
    public function down(): void {}
};
