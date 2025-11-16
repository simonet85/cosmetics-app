<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ["name" => "Skincare", "slug" => "skincare", "image" => "images/hero-slider/hero-slider-white-08.jpg", "is_active" => true, "order" => 1],
            ["name" => "Makeup", "slug" => "makeup", "image" => "images/hero-slider/hero-slider-white-09.jpg", "is_active" => true, "order" => 2],
            ["name" => "Hair Care", "slug" => "hair-care", "image" => "images/hero-slider/hero-slider-white-10.jpg", "is_active" => true, "order" => 3],
            ["name" => "Fragrance", "slug" => "fragrance", "image" => "images/hero-slider/hero-slider-white-11.jpg", "is_active" => true, "order" => 4],
            ["name" => "Body Care", "slug" => "body-care", "image" => "images/hero-slider/hero-slider-white-12.jpg", "is_active" => true, "order" => 5],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}