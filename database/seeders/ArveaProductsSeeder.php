<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Natural care products of the Arvea Nature range (names, descriptions, usage, ingredients, precautions, storage and photos
 * read from the brand's public pages, with the owner's permission). Prices there are in Algerian dinars: they are converted
 * to FCFA at RATE and can be changed in the admin. The texts and photos are in database/seeders/data/arvea_products.json
 * and public/images/products/arvea/.
 *
 * A product that already exists (same slug) is left alone, so running the seeder again never overwrites an edit made in the admin.
 */
class ArveaProductsSeeder extends Seeder
{
    /** FCFA for one Algerian dinar. */
    public const RATE = 4.5;

    private const STOCK = 20;

    public function run(): void
    {
        $file = database_path('seeders/data/arvea_products.json');
        $products = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        $tag = Tag::firstOrCreate(['slug' => 'arvea'], ['name' => 'Arvea']);

        foreach ($products as $data) {
            if (Product::withTrashed()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            [$price, $discount] = self::prices($data['price_dz'], $data['old_price_dz'] ?? null);

            $product = Product::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'sku' => 'ARV-'.$data['ref'],
                'price' => $price,
                'discount_price' => $discount,
                'discount_percentage' => $discount ? (int) round((1 - $discount / $price) * 100) : null,
                'stock' => self::STOCK,
                'is_active' => true,
                'is_archived' => false,
                'is_featured' => (bool) $data['featured'],
                'short_description' => $data['short_description'],
                'full_description' => $data['description'],
                'how_to_use' => $data['how_to_use'] ?: null,
                'ingredients' => $data['ingredients'] ?: null,
                'key_ingredients' => $data['key_ingredients'] ?: null,
                'precautions' => $data['precautions'] ?: null,
                'storage_conditions' => $data['storage_conditions'] ?: null,
            ]);

            foreach ($data['images'] as $position => $file) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => 'images/products/arvea/'.$file,
                    'is_primary' => $position === 0,
                    'order' => $position + 1,
                ]);
            }

            if ($category = Category::where('slug', $data['category'])->first()) {
                $product->categories()->attach($category);
            }
            $product->tags()->attach($tag);
        }
    }

    /**
     * FCFA prices from dinars. When the site shows a crossed-out old price, that one is the list price and the other the promotion.
     *
     * @return array{0: float, 1: float|null} [price, discount price]
     */
    public static function prices(int $dinars, ?int $oldDinars): array
    {
        if ($oldDinars !== null && $oldDinars > $dinars) {
            return [round($oldDinars * self::RATE), round($dinars * self::RATE)];
        }

        return [round($dinars * self::RATE), null];
    }
}
