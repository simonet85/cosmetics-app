<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * The demo catalogue was seeded in English. French is the shop's language, so this turns the demo rows into French
 * and keeps the English original as a hand-typed English translation (no DeepL needed for the demo).
 * Safe to run again: a row whose text is no longer the English original (edited in the admin) is left alone.
 */
class DemoFrenchSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->categories() as $slug => $fields) {
            $this->convert(Category::where('slug', $slug)->first(), $fields);
        }

        foreach ($this->products() as $slug => $fields) {
            $this->convert(Product::where('slug', $slug)->first(), $fields);
        }

        // Banners have no slug: they are found by their English title
        foreach ($this->banners() as $englishTitle => $fields) {
            $this->convert(Banner::where('title', $englishTitle)->first(), $fields, $englishTitle);
        }
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $fields  field => [English original, French]
     */
    private function convert(?Model $model, array $fields, ?string $englishTitle = null): void
    {
        if (! $model) {
            return;
        }

        foreach ($fields as $field => [$english, $french]) {
            $current = $model->getAttribute($field);

            if ($current !== $english && ! ($englishTitle !== null && $field === 'title')) {
                continue;
            }

            $model->setAttribute($field, $french);

            Translation::updateOrCreate(
                ['translatable_type' => $model->getMorphClass(), 'translatable_id' => $model->getKey(), 'field' => $field, 'locale' => 'en'],
                ['value' => $english, 'source_hash' => null],
            );
        }

        $model->save();
    }

    private function categories(): array
    {
        return [
            'skincare' => ['name' => ['Skincare', 'Soins du visage']],
            'makeup' => ['name' => ['Makeup', 'Maquillage']],
            'hair-care' => ['name' => ['Hair Care', 'Soins capillaires']],
            'fragrance' => ['name' => ['Fragrance', 'Parfums']],
            'body-care' => ['name' => ['Body Care', 'Soins du corps']],
        ];
    }

    private function products(): array
    {
        return [
            'natural-coconut-cleansing-oil' => [
                'name' => ['Natural Coconut Cleansing Oil', 'Huile démaquillante à la noix de coco'],
                'short_description' => ['Gentle cleansing oil for all skin types', 'Huile nettoyante douce pour tous les types de peau'],
                'full_description' => ['Our Natural Coconut Cleansing Oil gently removes makeup and impurities while nourishing your skin with organic coconut oil.', 'Notre huile démaquillante à la noix de coco élimine en douceur le maquillage et les impuretés tout en nourrissant la peau grâce à l\'huile de coco biologique.'],
            ],
            'hydrating-face-serum' => [
                'name' => ['Hydrating Face Serum', 'Sérum visage hydratant'],
                'short_description' => ['Intensive hydration serum', 'Sérum d\'hydratation intense'],
                'full_description' => ['Deep hydration formula that penetrates skin layers to provide lasting moisture and radiance.', 'Une formule d\'hydratation profonde qui pénètre les couches de la peau pour une hydratation durable et un teint éclatant.'],
            ],
            'vitamin-c-brightening-cream' => [
                'name' => ['Vitamin C Brightening Cream', 'Crème éclat à la vitamine C'],
                'short_description' => ['Brightens and evens skin tone', 'Illumine et unifie le teint'],
                'full_description' => ['Advanced vitamin C formula that brightens skin, reduces dark spots, and evens skin tone.', 'Une formule avancée à la vitamine C qui illumine la peau, atténue les taches brunes et unifie le teint.'],
            ],
            'organic-rose-water-toner' => [
                'name' => ['Organic Rose Water Toner', 'Lotion à l\'eau de rose bio'],
                'short_description' => ['Refreshing rose water toner', 'Lotion tonique rafraîchissante à l\'eau de rose'],
                'full_description' => ['Pure organic rose water that refreshes, tones, and balances your skin\'s pH.', 'Une eau de rose biologique pure qui rafraîchit, tonifie et équilibre le pH de la peau.'],
            ],
            'nourishing-night-cream' => [
                'name' => ['Nourishing Night Cream', 'Crème de nuit nourrissante'],
                'short_description' => ['Rich night cream for deep nourishment', 'Crème de nuit riche pour une nutrition en profondeur'],
                'full_description' => ['Luxurious night cream that works while you sleep to repair and nourish your skin.', 'Une crème de nuit luxueuse qui agit pendant votre sommeil pour réparer et nourrir la peau.'],
            ],
            'exfoliating-face-scrub' => [
                'name' => ['Exfoliating Face Scrub', 'Gommage exfoliant visage'],
                'short_description' => ['Gentle exfoliating scrub', 'Gommage exfoliant doux'],
                'full_description' => ['Natural exfoliating scrub that removes dead skin cells and reveals smoother, brighter skin.', 'Un gommage naturel qui élimine les cellules mortes et révèle une peau plus douce et plus lumineuse.'],
            ],
            'anti-aging-eye-cream' => [
                'name' => ['Anti-Aging Eye Cream', 'Crème contour des yeux anti-âge'],
                'short_description' => ['Reduces fine lines and wrinkles', 'Réduit les ridules et les rides'],
                'full_description' => ['Targeted eye cream that reduces the appearance of fine lines, wrinkles, and dark circles.', 'Un soin ciblé qui atténue l\'apparence des ridules, des rides et des cernes.'],
            ],
            'matte-finish-foundation' => [
                'name' => ['Matte Finish Foundation', 'Fond de teint effet mat'],
                'short_description' => ['Long-lasting matte foundation', 'Fond de teint mat longue tenue'],
                'full_description' => ['Lightweight matte foundation that provides full coverage and lasts all day.', 'Un fond de teint mat léger à couvrance totale qui tient toute la journée.'],
            ],
            'volumizing-mascara' => [
                'name' => ['Volumizing Mascara', 'Mascara volume'],
                'short_description' => ['Dramatic volume and length', 'Volume et longueur spectaculaires'],
                'full_description' => ['Creates dramatic volume and length for your lashes without clumping or smudging.', 'Donne un volume et une longueur spectaculaires aux cils, sans paquets ni traces.'],
            ],
            'nourishing-hair-oil' => [
                'name' => ['Nourishing Hair Oil', 'Huile nourrissante pour cheveux'],
                'short_description' => ['Strengthens and shines hair', 'Fortifie et fait briller les cheveux'],
                'full_description' => ['Nourishing hair oil blend that strengthens, repairs, and adds brilliant shine to your hair.', 'Un mélange d\'huiles nourrissantes qui fortifie, répare et donne un éclat brillant aux cheveux.'],
            ],
        ];
    }

    private function banners(): array
    {
        return [
            "Be Your\nKind of Beauty" => [
                'title' => ["Be Your\nKind of Beauty", "Soyez belle,\nà votre façon"],
                'subtitle' => ['gift for your skin', 'un cadeau pour votre peau'],
                'description' => ['Made using clean, non-toxic ingredients, our products are designed for everyone.', 'Des produits aux ingrédients propres et non toxiques, pensés pour toutes et tous.'],
                'button_text' => ['Shop Now', 'Voir la boutique'],
            ],
            "Natural Beauty\nStarts Here" => [
                'title' => ["Natural Beauty\nStarts Here", "La beauté naturelle\ncommence ici"],
                'subtitle' => ['discover glowing skin', 'révélez l\'éclat de votre peau'],
                'description' => ['Transform your skin with our organic, cruelty-free beauty products.', 'Transformez votre peau avec nos produits de beauté bio, non testés sur les animaux.'],
                'button_text' => ['Explore Now', 'Découvrir'],
            ],
            "Radiant Skin\nNaturally" => [
                'title' => ["Radiant Skin\nNaturally", "Une peau éclatante,\nnaturellement"],
                'subtitle' => ['pure ingredients', 'ingrédients purs'],
                'description' => ['Experience the power of nature with our carefully curated skincare collection.', 'Découvrez la force de la nature avec notre collection de soins soigneusement choisie.'],
                'button_text' => ['Shop Collection', 'Voir la collection'],
            ],
            'Intensive Glow C+ Serum' => [
                'title' => ['Intensive Glow C+ Serum', 'Sérum Éclat Intense C+'],
                'subtitle' => ['new arrival', 'nouveauté'],
                'description' => ['Brighten and even your skin tone with vitamin C.', 'Illumine et unifie le teint grâce à la vitamine C.'],
                'button_text' => ['Explore More', 'En savoir plus'],
            ],
            '25% off Everything' => [
                'title' => ['25% off Everything', '-25 % sur tout'],
                'subtitle' => ['summer sale', 'soldes d\'été'],
                'description' => ['Limited time offer on all products.', 'Offre à durée limitée sur tous les produits.'],
                'button_text' => ['Shop Now', 'Voir la boutique'],
            ],
        ];
    }
}
