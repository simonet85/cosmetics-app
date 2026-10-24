# Accueil : brief de redesign (à valider avant tout code)

Suite de [storefront-redesign.md](storefront-redesign.md), étape 1 « Accueil ». Faits produit : `PRODUCT.md`. Monde visuel : `design-system/glowing-cosmetics/MASTER.md` (pharmacie dermo-cosmétique, **conservé** : vert `#0B7A4B`, Archivo, à plat, aucune ombre, jamais la croix verte).

## Constat (réponses du propriétaire)

L'accueil actuel est jugé **plat / pas assez vendeur**, **chargé / mal hiérarchisé** et **générique**. Lecture de la page actuelle : un slider 1→2→3 occupe le premier écran, puis une longue bande verte de rayons en texte seul, puis une liste de huit produits en lignes, puis les avis, puis une bannière. Aucune preuve (livraison, paiement) n'est visible hors du bandeau d'en-tête, les rayons n'ont pas d'image, la sélection se lit comme un tableau et non comme une vitrine.

## Travail et public

- **Surface** : `/` (mode **Persuade**). Acheteuses d'Abidjan et d'Afrique francophone, sur téléphone (360 à 430 px), souvent sans compte, qui veulent savoir en 5 secondes ce que vend Glowing, si c'est fiable et comment commander.
- **Action principale** : ajouter un produit au panier ou ouvrir la boutique. **Secondaire** : entrer par un rayon.
- **Réussite** : on comprend l'offre au premier écran ; au moins un produit est achetable sans quitter l'accueil ; Lighthouse mobile ≥ 90 (performance et accessibilité).

## Thèse de la nouvelle structure

Un **comptoir** : une seule idée par écran, de haut en bas, chacune avec une photo ou un chiffre réel. On supprime le décor, on garde l'étiquette de pharmacie comme signature (nom, contenance, prix net en grands chiffres, ticket de prix).

## Séquence (mobile d'abord, 390 px ; le bureau étire en colonnes)

1. **Hero** (premier écran) : l'accroche et la photo de la première bannière, un bouton « Voir la boutique », et juste dessous **trois preuves lues des réglages** : livraison gratuite dès le seuil, moyens de paiement acceptés, commande sans compte. Le slider 1→2→3 reste la mécanique du hero (voir décision 1), mais **plus léger** : un seul texte, une seule action par diapositive, indicateurs et pause conservés.
2. **Les rayons** : des **tuiles avec image** (champ `image` de la catégorie), 2 colonnes sur mobile, 3 à 5 sur bureau, nom en capitales condensées. Remplace la bande verte en liste.
3. **La sélection** : une **grille de cartes** (2 colonnes mobile, 4 bureau, 4 à 8 produits) avec le ticket de prix, la remise, l'indication de stock et « Ajouter au panier ». Remplace la liste en lignes ; le produit en vedette n'est plus dupliqué.
4. **Nos engagements** : une bande verte pleine largeur de **trois faits vérifiables** seulement (livraison dès le seuil, paiement mobile money / à la livraison / virement selon les réglages, commande sans compte). Aucun label « bio » ou « sans cruauté » tant qu'il n'est pas documenté.
5. **Avis clients** : jusqu'à 3 témoignages réels avec note ; la section disparaît s'il n'y en a pas.
6. **Appel final** : la bannière du bas si elle existe, sinon un bouton « Voir toute la boutique ».

## États et plages réelles

- Rayons : 0 à 8 (au-delà de 6, on cache le reste derrière « Tous les rayons »). Sans image : tuile verte avec le nom seul.
- Produits en vedette : 0 à 8 ; sans vedette, repli sur meilleures ventes puis nouveautés ; aucun : état vide actuel.
- Bannières : chacune optionnelle ; aucune : le hero devient le produit en vedette seul.
- Témoignages : 0 à 3.
- Anglais et français : tous les textes par `__()`, contenus par `tr()`.

## Interaction et mouvement

Apparition au défilement existante (`data-reveal`), ticket de prix qui sort une seule fois, tout désactivé en `prefers-reduced-motion`. Ajout au panier sans rechargement (existant). Zones tactiles ≥ 44 px, un seul `h1`, titres h2 pour chaque section.

## Contraintes et hors périmètre

Blade + Tailwind v4 + JavaScript natif, aucun prix ni stock écrit dans la vue, WCAG 2.2 AA, images avec dimensions et `loading` (le hero en `eager`/prioritaire). **Inchangés** : routes, contrôleur, données, en-tête, pied de page, autres pages, tests existants (adaptés seulement là où le balisage de l'accueil change).

## Décisions (tranchées par le propriétaire)

1. **Slider 1→2→3** : conservé dans le hero, allégé.
2. **Images des rayons** : pas de vraies photos de rayons, donc chaque tuile montre la photo du premier produit en vente du rayon ; un rayon sans produit en vente n'est pas proposé sur l'accueil.
3. **Photo du hero** : celle de la bannière gérée dans l'administration ; à vérifier fichier par fichier avant de la présenter comme réelle.

## État

Construit : bande de preuves lues des réglages sous le hero, rayons en tuiles, sélection en grille de cartes, bande « Commander sans surprise » (trois faits vérifiables), 3 avis au plus, appel final. Tests : `HomeRedesignTest` (8) ; vérifié à 390 px et 1440 px, sans débordement horizontal.
