<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-deleted products keep their unique sku/slug and block reuse. Give them the same
     * "-deleted-{id}" suffix that Product::booted() now applies when a product is deleted.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereNotNull('deleted_at')
            ->get(['id', 'sku', 'slug'])
            ->each(function ($product) {
                $suffix = '-deleted-'.$product->id;

                DB::table('products')->where('id', $product->id)->update([
                    'sku' => str_ends_with($product->sku, $suffix) ? $product->sku : $product->sku.$suffix,
                    'slug' => str_ends_with($product->slug, $suffix) ? $product->slug : $product->slug.$suffix,
                ]);
            });
    }

    /**
     * Not reversible without losing which rows were renamed by hand; the suffix is harmless.
     */
    public function down(): void {}
};
