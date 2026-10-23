<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** @var list<string> */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sérum éclat',
            'sku' => 'SER-001',
            'description' => 'Une description complète',
            'short_description' => 'Court',
            'price' => 15000,
            'stock' => 12,
            'status' => 'active',
        ], $overrides);
    }

    public function test_guests_and_customers_cannot_reach_the_admin_products(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_index_lists_products_and_supports_search_and_status_filters(): void
    {
        Product::factory()->create(['name' => 'Crème visage', 'full_description' => 'hydratant intense']);
        Product::factory()->create(['name' => 'Huile cheveux', 'is_active' => false]);
        Product::factory()->create(['name' => 'Ancien produit', 'is_archived' => true]);

        $this->actingAs($this->admin)->get(route('admin.products.index'))
            ->assertOk()->assertSee('Crème visage')->assertSee('Huile cheveux')->assertSee('Archivé');

        $this->actingAs($this->admin)->get(route('admin.products.index', ['search' => 'hydratant']))
            ->assertOk()->assertSee('Crème visage')->assertDontSee('Huile cheveux');

        $this->actingAs($this->admin)->get(route('admin.products.index', ['status' => 'inactive']))
            ->assertOk()->assertSee('Huile cheveux')->assertDontSee('Crème visage');

        $this->actingAs($this->admin)->get(route('admin.products.index', ['status' => 'archived']))
            ->assertOk()->assertSee('Ancien produit')->assertDontSee('Crème visage');
    }

    public function test_index_ignores_an_unknown_sort_column(): void
    {
        Product::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.products.index', ['sort_by' => 'does_not_exist', 'sort_order' => 'sideways']))
            ->assertOk();
    }

    public function test_create_and_edit_forms_render(): void
    {
        $product = Product::factory()->create(['full_description' => 'Texte existant']);

        $this->actingAs($this->admin)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.products.edit', $product))
            ->assertOk()->assertSee('Texte existant');
    }

    public function test_store_maps_the_form_onto_the_real_columns(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload([
                'categories' => [$category->id],
                'is_featured' => '1',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'SER-001')->firstOrFail();

        $this->assertSame('Une description complète', $product->full_description);
        $this->assertTrue($product->is_active);
        $this->assertFalse($product->is_archived);
        $this->assertTrue($product->is_featured);
        $this->assertSame('active', $product->status);
        $this->assertSame('serum-eclat', $product->slug);
        $this->assertTrue($product->categories->contains($category));
    }

    public function test_store_keeps_slugs_unique(): void
    {
        Product::factory()->create(['slug' => 'serum-eclat']);

        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload());

        $this->assertSame('serum-eclat-1', Product::where('sku', 'SER-001')->value('slug'));
    }

    public function test_store_validates_required_fields_and_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload(['name' => '', 'status' => 'draft']))
            ->assertSessionHasErrors(['name', 'status']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_status_inactive_and_archived_set_the_flags(): void
    {
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload(['status' => 'inactive']));
        $this->actingAs($this->admin)->post(route('admin.products.store'), $this->payload(['name' => 'B', 'sku' => 'B-1', 'status' => 'archived']));

        $inactive = Product::where('sku', 'SER-001')->first();
        $archived = Product::where('sku', 'B-1')->first();

        $this->assertFalse($inactive->is_active);
        $this->assertFalse($inactive->is_archived);
        $this->assertTrue($archived->is_archived);
        $this->assertSame('archived', $archived->status);
    }

    public function test_update_changes_fields_and_can_unfeature(): void
    {
        $product = Product::factory()->create(['is_featured' => true, 'name' => 'Ancien nom', 'slug' => 'ancien-nom']);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $this->payload([
                'name' => 'Nouveau nom',
                'sku' => $product->sku,
                'status' => 'inactive',
            ]))
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();

        $this->assertSame('Nouveau nom', $product->name);
        $this->assertSame('nouveau-nom', $product->slug);
        $this->assertFalse($product->is_featured);
        $this->assertFalse($product->is_active);
        $this->assertSame('Une description complète', $product->full_description);
    }

    public function test_images_can_be_uploaded_and_the_first_becomes_primary(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('admin.products.images.upload', $product), [
                'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $images = $product->images()->get();
        $this->assertCount(2, $images);
        $this->assertTrue($images[0]->is_primary);
        $this->assertFalse($images[1]->is_primary);

        foreach ($images as $image) {
            $this->createdFiles[] = public_path($image->path);
            $this->assertFileExists(public_path($image->path));
        }
    }

    public function test_store_saves_uploaded_images(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), $this->payload(['images' => [UploadedFile::fake()->image('a.jpg')]]))
            ->assertRedirect(route('admin.products.index'));

        $image = Product::where('sku', 'SER-001')->firstOrFail()->images()->firstOrFail();
        $this->createdFiles[] = public_path($image->path);

        $this->assertTrue($image->is_primary);
        $this->assertFileExists(public_path($image->path));
    }

    public function test_destroy_removes_the_product_and_its_image_files(): void
    {
        $product = Product::factory()->create();
        $file = public_path('storage/products/test-destroy-'.uniqid().'.jpg');
        File::ensureDirectoryExists(dirname($file));
        File::put($file, 'x');
        $this->createdFiles[] = $file;
        ProductImage::create(['product_id' => $product->id, 'path' => 'storage/products/'.basename($file), 'is_primary' => true, 'order' => 0]);

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertFileDoesNotExist($file);
    }
}
