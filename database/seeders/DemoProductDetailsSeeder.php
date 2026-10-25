<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Information tabs (utilisation, ingrédients, précautions d'emploi, conditions de conservation) of the demo products.
 *
 * DEMO CONTENT: the ingredient lists are illustrative, to show how the product page reads. They must be replaced by the
 * real INCI list printed on each product before it is sold: a wrong composition is wrong safety information.
 * A field that already has a text (typed in the admin) is never overwritten.
 */
class DemoProductDetailsSeeder extends Seeder
{
    private const EXTERNAL_USE = 'Pour usage externe uniquement. En cas d\'irritation, arrêter l\'utilisation et consulter un professionnel de santé.';

    public function run(): void
    {
        foreach ($this->products() as $slug => $fields) {
            $product = Product::where('slug', $slug)->first();
            if (! $product) {
                continue;
            }

            foreach ($fields as $field => $value) {
                if (blank($product->{$field})) {
                    $product->{$field} = $value;
                }
            }
            $product->save();
        }
    }

    private function products(): array
    {
        $storage = 'Conserver hors de portée des enfants. Protéger du rayonnement solaire et de la chaleur. Refermer soigneusement après usage.';

        return [
            'natural-coconut-cleansing-oil' => [
                'how_to_use' => "Masser 2 à 3 pressions sur le visage sec, contour des yeux compris, en mouvements circulaires pour dissoudre le maquillage.\nHumidifier les mains, émulsionner, puis rincer abondamment à l'eau tiède.",
                'ingredients' => 'COCOS NUCIFERA (COCONUT) OIL, CAPRYLIC/CAPRIC TRIGLYCERIDE, POLYGLYCERYL-4 ISOSTEARATE, TOCOPHEROL.',
                'key_ingredients' => "Huile de noix de coco : Nourrissante / Adoucissante / Démaquillante\nVitamine E (tocophérol) : Antioxydante / Protectrice",
                'precautions' => 'Pour usage externe uniquement. Éviter le contact avec les yeux ; en cas de contact, rincer immédiatement à l\'eau. En cas d\'irritation, arrêter l\'utilisation.',
                'storage_conditions' => $storage,
            ],
            'hydrating-face-serum' => [
                'how_to_use' => "Déposer 2 à 3 gouttes sur le visage et le cou propres, matin et soir, avant la crème.\nFaire pénétrer par de légères pressions.",
                'ingredients' => 'AQUA (WATER), GLYCERIN, SODIUM HYALURONATE, PANTHENOL, ALLANTOIN, PROPANEDIOL, PHENOXYETHANOL, ETHYLHEXYLGLYCERIN.',
                'key_ingredients' => "Acide hyaluronique : Hydratant / Repulpant\nPanthénol : Apaisant / Réparateur\nGlycérine : Hydratante",
                'precautions' => 'Pour usage externe uniquement. Éviter le contour immédiat des yeux. Faire un test sur le pli du coude avant la première utilisation.',
                'storage_conditions' => $storage,
            ],
            'vitamin-c-brightening-cream' => [
                'how_to_use' => "Appliquer le matin sur une peau propre et sèche, visage et cou, puis masser jusqu'à absorption.\nUtiliser une protection solaire dans la journée.",
                'ingredients' => 'AQUA (WATER), ASCORBYL GLUCOSIDE, GLYCERIN, BUTYROSPERMUM PARKII BUTTER, CETEARYL ALCOHOL, TOCOPHEROL, PHENOXYETHANOL.',
                'key_ingredients' => "Vitamine C : Éclat / Antioxydante / Unifie le teint\nBeurre de karité : Nourrissant / Adoucissant",
                'precautions' => self::EXTERNAL_USE.' Éviter le contact avec les yeux. Une protection solaire est recommandée pendant l\'utilisation.',
                'storage_conditions' => $storage,
            ],
            'organic-rose-water-toner' => [
                'how_to_use' => 'Après le nettoyage, vaporiser sur le visage et le cou, ou appliquer avec un coton. Laisser sécher puis poursuivre avec le soin habituel.',
                'ingredients' => 'ROSA DAMASCENA FLOWER WATER, GLYCERIN, ALOE BARBADENSIS LEAF JUICE, PROPANEDIOL, CITRIC ACID, PHENOXYETHANOL.',
                'key_ingredients' => "Eau de rose : Rafraîchissante / Tonifiante / Apaisante\nAloe vera : Hydratant / Apaisant",
                'precautions' => 'Pour usage externe uniquement. Fermer les yeux pendant la vaporisation. En cas de contact avec les yeux, rincer à l\'eau.',
                'storage_conditions' => 'Conserver hors de portée des enfants, à l\'abri de la lumière et de la chaleur. Refermer après usage.',
            ],
            'nourishing-night-cream' => [
                'how_to_use' => "Le soir, appliquer sur le visage et le cou propres, puis masser jusqu'à absorption.",
                'ingredients' => 'AQUA (WATER), BUTYROSPERMUM PARKII BUTTER, GLYCERIN, CETEARYL ALCOHOL, SQUALANE, TOCOPHEROL, PHENOXYETHANOL.',
                'key_ingredients' => "Beurre de karité : Nourrissant / Réparateur\nSqualane : Adoucissant / Protecteur",
                'precautions' => self::EXTERNAL_USE.' Éviter le contact avec les yeux.',
                'storage_conditions' => $storage,
            ],
            'exfoliating-face-scrub' => [
                'how_to_use' => "Une à deux fois par semaine, appliquer sur peau humide et masser doucement en évitant le contour des yeux.\nRincer abondamment à l'eau tiède.",
                'ingredients' => 'AQUA (WATER), GLYCERIN, SUCROSE, KAOLIN, CETEARYL ALCOHOL, PROPANEDIOL, PHENOXYETHANOL.',
                'key_ingredients' => "Sucre : Exfoliant doux\nKaolin : Purifiant / Matifiant",
                'precautions' => 'Pour usage externe uniquement. Ne pas appliquer sur une peau lésée, irritée ou brûlée par le soleil. Éviter le contact avec les yeux.',
                'storage_conditions' => $storage,
            ],
            'anti-aging-eye-cream' => [
                'how_to_use' => "Matin et soir, déposer une petite quantité sur le contour de l'œil et l'étaler du bout du doigt par de légers tapotements.",
                'ingredients' => 'AQUA (WATER), GLYCERIN, CAFFEINE, SODIUM HYALURONATE, TOCOPHEROL, PROPANEDIOL, PHENOXYETHANOL.',
                'key_ingredients' => "Caféine : Décongestionnante / Tonifiante\nAcide hyaluronique : Hydratant / Repulpant",
                'precautions' => 'Réservé au contour de l\'œil, à appliquer sur l\'os orbitaire. Éviter tout contact direct avec l\'œil ; en cas de contact, rincer abondamment à l\'eau.',
                'storage_conditions' => $storage,
            ],
            'matte-finish-foundation' => [
                'how_to_use' => "Appliquer au pinceau, à l'éponge ou du bout des doigts sur une peau préparée, du centre du visage vers l'extérieur.\nEstomper puis superposer une seconde couche si besoin.",
                'ingredients' => 'AQUA (WATER), CYCLOPENTASILOXANE, TALC, GLYCERIN, TITANIUM DIOXIDE, PHENOXYETHANOL, IRON OXIDES.',
                'key_ingredients' => 'Pigments minéraux : Couvrance / Teint uniforme',
                'precautions' => self::EXTERNAL_USE.' Éviter le contact avec les yeux. Retirer le maquillage chaque soir.',
                'storage_conditions' => 'Conserver hors de portée des enfants, à l\'abri de la chaleur et de la lumière. Bien refermer après usage.',
            ],
            'volumizing-mascara' => [
                'how_to_use' => "Appliquer de la racine vers la pointe des cils, en zigzag. Laisser sécher entre deux couches.\nDémaquiller chaque soir.",
                'ingredients' => 'AQUA (WATER), CERA ALBA, GLYCERYL STEARATE, PANTHENOL, TOCOPHEROL, PHENOXYETHANOL, IRON OXIDES.',
                'key_ingredients' => "Cires naturelles : Volume / Tenue\nPanthénol : Soin des cils",
                'precautions' => 'Ne pas utiliser en cas d\'irritation des yeux ou d\'infection oculaire. En cas de contact avec l\'œil, rincer abondamment à l\'eau. Ne pas partager le produit.',
                'storage_conditions' => 'Refermer soigneusement après chaque usage. Conserver à l\'abri de la chaleur. Il est recommandé de le remplacer environ 3 mois après ouverture.',
            ],
            'nourishing-hair-oil' => [
                'how_to_use' => 'Déposer 2 à 3 gouttes dans les mains et appliquer sur les longueurs et les pointes, sur cheveux secs ou humides. Ne pas rincer.',
                'ingredients' => 'ARGANIA SPINOSA KERNEL OIL, SIMMONDSIA CHINENSIS SEED OIL, TOCOPHEROL, PARFUM (FRAGRANCE).',
                'key_ingredients' => "Huile d'argan : Nourrissante / Fortifiante\nHuile de jojoba : Adoucissante / Apporte de la brillance",
                'precautions' => 'Pour usage externe uniquement. Éviter le contact avec les yeux ; en cas de contact, rincer immédiatement à l\'eau. Ne pas avaler.',
                'storage_conditions' => $storage,
            ],
        ];
    }
}
