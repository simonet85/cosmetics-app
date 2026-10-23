<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sérum éclat',
            'sku' => 'SER-001',
            'description' => 'Description',
            'price' => 15000,
            'stock' => 5,
            'status' => 'active',
        ], $overrides);
    }

    public function test_soft_deleting_frees_the_sku_and_slug(): void
    {
        $product = Product::factory()->create(['sku' => 'SER-001', 'slug' => 'serum-eclat']);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $row = Product::withTrashed()->find($product->id);
        $this->assertSame('SER-001-deleted-'.$product->id, $row->sku);
        $this->assertSame('serum-eclat-deleted-'.$product->id, $row->slug);
    }

    public function test_a_new_product_can_reuse_the_sku_and_name_of_a_deleted_one_through_the_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload());
        $first = Product::where('sku', 'SER-001')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.products.destroy', $first));
        $this->assertSoftDeleted('products', ['id' => $first->id]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->payload())
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $second = Product::where('sku', 'SER-001')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame('serum-eclat', $second->slug);
    }

    public function test_restoring_gives_back_the_original_sku_and_slug(): void
    {
        $product = Product::factory()->create(['sku' => 'SER-001', 'slug' => 'serum-eclat']);
        $product->delete();

        Product::withTrashed()->find($product->id)->restore();

        $product->refresh();
        $this->assertSame('SER-001', $product->sku);
        $this->assertSame('serum-eclat', $product->slug);
        $this->assertNull($product->deleted_at);
    }

    public function test_restoring_keeps_the_suffix_when_the_original_was_taken(): void
    {
        $old = Product::factory()->create(['sku' => 'SER-001', 'slug' => 'serum-eclat']);
        $old->delete();
        Product::factory()->create(['sku' => 'SER-001', 'slug' => 'serum-eclat']);

        Product::withTrashed()->find($old->id)->restore();

        $old->refresh();
        $this->assertSame('SER-001-deleted-'.$old->id, $old->sku);
        $this->assertSame('serum-eclat-deleted-'.$old->id, $old->slug);
    }

    public function test_force_deleting_does_not_rename_and_removes_the_row(): void
    {
        $product = Product::factory()->create();

        $product->forceDelete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_the_migration_renames_products_that_were_already_soft_deleted(): void
    {
        $product = Product::factory()->create(['sku' => 'OLD-1', 'slug' => 'old-1']);
        // Simulate a row soft-deleted before the model hook existed.
        DB::table('products')->where('id', $product->id)->update(['deleted_at' => now()]);

        (require database_path('migrations/2026_10_23_210000_free_sku_and_slug_of_soft_deleted_products.php'))->up();

        $row = DB::table('products')->find($product->id);
        $this->assertSame('OLD-1-deleted-'.$product->id, $row->sku);
        $this->assertSame('old-1-deleted-'.$product->id, $row->slug);

        // Running it again changes nothing.
        (require database_path('migrations/2026_10_23_210000_free_sku_and_slug_of_soft_deleted_products.php'))->up();
        $this->assertSame('OLD-1-deleted-'.$product->id, DB::table('products')->find($product->id)->sku);
    }
}
