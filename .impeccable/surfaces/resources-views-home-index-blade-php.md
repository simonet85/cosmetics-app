---
version: 1
slug: "resources-views-home-index-blade-php"
primary_target: "resources/views/home/index.blade.php"
related_targets: []
---

# Accueil de la boutique

## Surface
Page d'accueil `resources/views/home/index.blade.php`. Mode : **Persuade**. La visiteuse décide et agit : repérer un produit, lui faire confiance, l'ajouter au panier.

## Public, tâche, preuve, contraintes
- Public : acheteuses d'Abidjan et d'Afrique francophone, sur téléphone (360 à 430 px), souvent sans compte.
- Action principale : ajouter un produit au panier (le panier vit déjà côté serveur) ; action secondaire : ouvrir la boutique.
- Preuve réelle uniquement : produits et prix de la base, seuil de livraison gratuite et moyens de paiement lus des réglages du site, avis et témoignages réels. Aucun label « bio » ou « sans cruauté », chiffre de ventes ou délai de livraison n'est affiché sans donnée réelle.
- Contraintes : Blade + Tailwind v4, JavaScript natif, aucun prix ni stock écrit dans la vue, WCAG 2.2 AA, `prefers-reduced-motion`.

## Direction choisie et moment mémorable
Direction : *la pharmacie dermo-cosmétique* (attribuée par le tirage, retenue par le propriétaire). Moment mémorable : le **ticket de prix** du produit en vedette sort de l'étiquette, une seule fois, quand elle entre à l'écran (animation désactivée en mouvement réduit).

## Décisions ouvertes
- Quelles photos réelles servent au hero (à vérifier une par une dans `public/images/`).
- Quels produits sont « en vedette » (champ `is_featured` de la base ; aucun produit n'est choisi à la main dans la vue).

## Direction contract

THESIS: L'accueil est un comptoir de pharmacie dermo-cosmétique : chaque produit se lit comme une étiquette (nom, contenance, composition, prix net) avant d'être un décor. Il refuse le hero crème et serif avec une rangée de cartes égales.

OWN-WORLD: Blanc de rayonnage, aplats vert pharmacie `#0B7A4B` pleine largeur (30 à 60 % de la page), bandeaux menthe `#E3F0E8`, encre `#13201A`, vert sauge de la marque en appoint. Archivo pour tout : capitales condensées pour les étiquettes, chiffres tabulaires pour les prix. Filets fins, tickets de prix à angles droits, aucune ombre, jamais la croix verte (signe réservé aux pharmacies).

STORY: Une visiteuse sur téléphone comprend dès la première vue ce que vend Glowing, croit à une sélection courte aux compositions lisibles et aux prix nets, et peut ajouter au panier sans quitter l'accueil.

FIRST VIEWPORT: Sur 390 px, un bandeau vert pleine largeur (livraison gratuite dès le seuil des réglages, paiements acceptés), puis la grande étiquette du produit en vedette : photo réelle, nom en capitales condensées, contenance, trois lignes de composition, prix net en grands chiffres tabulaires, stock, et le bouton « Ajouter au panier » dans la zone du pouce. Sur ordinateur, l'étiquette occupe la colonne gauche et le bandeau vert la traverse en haut.

FORM: la pharmacie dermo-cosmétique, position 3 de ma liste de sept pistes, seed:60c08d599c91140b.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
