# Modèles de page commutables (accueil et boutique)

Demande du propriétaire (2026-10-25) : les deux maquettes « Glowing Beauty » (accueil et boutique, `images/pages/`) deviennent des **modèles que l'on choisit dans l'admin**, Réglages > Apparence. Ordre décidé : d'abord le mécanisme et le modèle de boutique, puis l'accueil.

## Règles communes

- Un modèle change la **présentation** d'une page, jamais ses données, ses routes, le panier ni le paiement. « Classique » reste le modèle par défaut.
- Réglages `storefront_template_home` et `storefront_template_shop` (groupe `appearance`). Une valeur inconnue retombe sur « classique ». Un administrateur connecté peut prévisualiser avec `?template=beauty` sans enregistrer.
- Le modèle « Glowing Beauty » a sa **propre identité** (décidée) : palette brune, titres à largeur normale. Elle est portée par la classe `.tpl-beauty` (jetons de couleur redéfinis dans la page, en-tête et pied de page inchangés). Les polices manuscrites des maquettes viendront avec l'accueil.
- On reprend la **mise en page** des maquettes, pas leurs visuels : les photos et produits des captures sont de marques tierces. Pas de blog (décidé : omis).

## Boutique (construite)

Bandeau de titre avec les rayons et leur nombre de produits en vente (un rayon vide n'est pas proposé) ; colonne de filtres (prix, contenance lue des variantes, promotion, épuisés) et « produits les mieux notés » (avis approuvés seulement) ; barre d'outils : produits par page (9, 12, 18, 24), colonnes (2, 3, 4) et tri avec envoi automatique ; grille de cartes avec badges calculés (HOT, NOUVEAU sur 30 jours, -N %, ÉPUISÉ), prix net, « Ajouter » et favoris ; pagination existante. Toutes les valeurs de la requête inconnues sont ignorées.

## Accueil (construit)

Mobile d'abord. Choix du propriétaire : les images de marques tierces et les images téléchargées sont autorisées (convertis en JPEG légers dans `public/images/templates/beauty/`), blog omis, compte à rebours oui.

1. **Hero : un slider** (grand visuel pleine largeur, flèches, points, pause). Bannières de l'admin d'abord ; sans bannière, une diapositive « marque » (photo `hero.jpg`) puis des produits en vedette jusqu'à trois diapositives.
2. **Cartes de rayons avec photo** (photo du premier produit en vente), jusqu'à trois.
3. **Meilleures ventes** et **En vedette** : titre manuscrit à gauche, trois produits à droite, jamais le même produit deux fois.
4. **Avant / après** : deux photos et un curseur natif (`input range` étiqueté) qui déplace la ligne ; rayons réels en liste ; texte neutre (« les résultats varient »).
5. **Offre avec compte à rebours** : Réglages > Apparence (date de fin, titre, texte). Sans date, ou date passée, la section disparaît. Le compte à rebours est calculé côté serveur puis mis à jour par JavaScript.
6. Police manuscrite : Satisfy (`@fontsource/satisfy`), seulement pour les accents de titres.

Tests : `StorefrontTemplatesTest`, `HomeBeautyTest`, et le parcours Playwright `tests/e2e/templates.spec.ts` (connexion admin locale, choix des modèles, vérification de l'accueil et de la boutique sur bureau et mobile, anglais, retour au modèle classique). À lancer un projet après l'autre : `--workers=1 --project=desktop` puis `--project=mobile`.
