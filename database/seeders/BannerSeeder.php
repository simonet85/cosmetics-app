<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Banner;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        // Hero Banners for carousel with real images
        Banner::create([
            "type" => "hero",
            "position" => "hero",
            "title" => "Be Your\nKind of Beauty",
            "subtitle" => "gift for your skin",
            "description" => "Made using clean, non-toxic ingredients, our products are designed for everyone.",
            "image_path" => "images/hero-slider/hero-slider-white-01.jpg",
            "link_url" => "/shop",
            "button_text" => "Shop Now",
            "is_active" => true,
            "order" => 1,
        ]);

        Banner::create([
            "type" => "hero",
            "position" => "hero",
            "title" => "Natural Beauty\nStarts Here",
            "subtitle" => "discover glowing skin",
            "description" => "Transform your skin with our organic, cruelty-free beauty products.",
            "image_path" => "images/hero-slider/hero-slider-white-02.jpg",
            "link_url" => "/shop",
            "button_text" => "Explore Now",
            "is_active" => true,
            "order" => 2,
        ]);

        Banner::create([
            "type" => "hero",
            "position" => "hero",
            "title" => "Radiant Skin\nNaturally",
            "subtitle" => "pure ingredients",
            "description" => "Experience the power of nature with our carefully curated skincare collection.",
            "image_path" => "images/hero-slider/hero-slider-white-03.jpg",
            "link_url" => "/shop",
            "button_text" => "Shop Collection",
            "is_active" => true,
            "order" => 3,
        ]);

        // Promotional Banners with real images
        Banner::create([
            "type" => "promo",
            "position" => "promo",
            "title" => "Intensive Glow C+ Serum",
            "subtitle" => "new arrival",
            "description" => "Brighten and even your skin tone with vitamin C.",
            "image_path" => "images/hero-slider/hero-slider-15.jpg",
            "link_url" => "/products/intensive-glow-serum",
            "button_text" => "Explore More",
            "is_active" => true,
            "order" => 1,
        ]);

        Banner::create([
            "type" => "promo",
            "position" => "promo",
            "title" => "25% off Everything",
            "subtitle" => "summer sale",
            "description" => "Limited time offer on all products.",
            "image_path" => "images/hero-slider/hero-slider-16.jpg",
            "link_url" => "/shop",
            "button_text" => "Shop Now",
            "is_active" => true,
            "order" => 2,
        ]);
    }
}