<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Get categories
        $skincare = Category::where('slug', 'skincare')->first();
        $makeup = Category::where('slug', 'makeup')->first();
        $haircare = Category::where('slug', 'haircare')->first();

        $products = [
            [
                "name" => "Natural Coconut Cleansing Oil",
                "slug" => "natural-coconut-cleansing-oil",
                "sku" => "SKU001",
                "price" => 29.99,
                "stock" => 50,
                "is_featured" => true,
                "is_active" => true,
                "short_description" => "Gentle cleansing oil for all skin types",
                "full_description" => "Our Natural Coconut Cleansing Oil gently removes makeup and impurities while nourishing your skin with organic coconut oil.",
                "category" => $skincare,
                "images" => ["images/products/product-01-330x440.jpg"]
            ],
            [
                "name" => "Hydrating Face Serum",
                "slug" => "hydrating-face-serum",
                "sku" => "SKU002",
                "price" => 45.00,
                "discount_price" => 39.99,
                "discount_percentage" => 11,
                "stock" => 30,
                "is_hot" => true,
                "is_featured" => true,
                "is_active" => true,
                "short_description" => "Intensive hydration serum",
                "full_description" => "Deep hydration formula that penetrates skin layers to provide lasting moisture and radiance.",
                "category" => $skincare,
                "images" => ["images/products/product-02-330x440.jpg"]
            ],
            [
                "name" => "Vitamin C Brightening Cream",
                "slug" => "vitamin-c-brightening-cream",
                "sku" => "SKU003",
                "price" => 55.00,
                "stock" => 25,
                "is_best_seller" => true,
                "is_featured" => true,
                "is_active" => true,
                "short_description" => "Brightens and evens skin tone",
                "full_description" => "Advanced vitamin C formula that brightens skin, reduces dark spots, and evens skin tone.",
                "category" => $skincare,
                "images" => ["images/products/product-03-330x440.jpg"]
            ],
            [
                "name" => "Organic Rose Water Toner",
                "slug" => "organic-rose-water-toner",
                "sku" => "SKU004",
                "price" => 19.99,
                "stock" => 40,
                "is_featured" => true,
                "is_active" => true,
                "short_description" => "Refreshing rose water toner",
                "full_description" => "Pure organic rose water that refreshes, tones, and balances your skin's pH.",
                "category" => $skincare,
                "images" => ["images/products/product-04-330x440.jpg"]
            ],
            [
                "name" => "Nourishing Night Cream",
                "slug" => "nourishing-night-cream",
                "sku" => "SKU005",
                "price" => 42.00,
                "stock" => 35,
                "is_best_seller" => true,
                "is_active" => true,
                "short_description" => "Rich night cream for deep nourishment",
                "full_description" => "Luxurious night cream that works while you sleep to repair and nourish your skin.",
                "category" => $skincare,
                "images" => ["images/products/product-05-330x440.jpg"]
            ],
            [
                "name" => "Exfoliating Face Scrub",
                "slug" => "exfoliating-face-scrub",
                "sku" => "SKU006",
                "price" => 24.99,
                "discount_price" => 19.99,
                "discount_percentage" => 20,
                "stock" => 45,
                "is_active" => true,
                "short_description" => "Gentle exfoliating scrub",
                "full_description" => "Natural exfoliating scrub that removes dead skin cells and reveals smoother, brighter skin.",
                "category" => $skincare,
                "images" => ["images/products/product-06-330x440.jpg"]
            ],
            [
                "name" => "Anti-Aging Eye Cream",
                "slug" => "anti-aging-eye-cream",
                "sku" => "SKU007",
                "price" => 38.00,
                "stock" => 20,
                "is_hot" => true,
                "is_active" => true,
                "short_description" => "Reduces fine lines and wrinkles",
                "full_description" => "Targeted eye cream that reduces the appearance of fine lines, wrinkles, and dark circles.",
                "category" => $skincare,
                "images" => ["images/products/product-07-330x440.jpg"]
            ],
            [
                "name" => "Matte Finish Foundation",
                "slug" => "matte-finish-foundation",
                "sku" => "SKU008",
                "price" => 32.00,
                "stock" => 60,
                "is_best_seller" => true,
                "is_active" => true,
                "short_description" => "Long-lasting matte foundation",
                "full_description" => "Lightweight matte foundation that provides full coverage and lasts all day.",
                "category" => $makeup,
                "images" => ["images/products/product-08-330x440.jpg"]
            ],
            [
                "name" => "Volumizing Mascara",
                "slug" => "volumizing-mascara",
                "sku" => "SKU009",
                "price" => 18.99,
                "stock" => 70,
                "is_hot" => true,
                "is_active" => true,
                "short_description" => "Dramatic volume and length",
                "full_description" => "Creates dramatic volume and length for your lashes without clumping or smudging.",
                "category" => $makeup,
                "images" => ["images/products/product-10-270x360.jpg"]
            ],
            [
                "name" => "Nourishing Hair Oil",
                "slug" => "nourishing-hair-oil",
                "sku" => "SKU010",
                "price" => 26.00,
                "discount_price" => 22.00,
                "discount_percentage" => 15,
                "stock" => 40,
                "is_best_seller" => true,
                "is_active" => true,
                "short_description" => "Strengthens and shines hair",
                "full_description" => "Nourishing hair oil blend that strengthens, repairs, and adds brilliant shine to your hair.",
                "category" => $haircare,
                "images" => ["images/products/product-11-270x360.jpg"]
            ],
        ];

        foreach ($products as $productData) {
            $images = $productData['images'];
            $category = $productData['category'] ?? null;
            unset($productData['images']);
            unset($productData['category']);

            $product = Product::create($productData);

            // Create product images
            foreach ($images as $index => $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $imagePath,
                    'is_primary' => $index === 0,
                    'order' => $index + 1,
                ]);
            }

            // Attach categories
            if ($category) {
                $product->categories()->attach($category->id);
            }
        }
    }
}
