# Modèles de page commutables (accueil et boutique)

Demande du propriétaire (2026-10-25) : les deux maquettes « Glowing Beauty » (accueil et boutique, `images/pages/`) deviennent des **modèles que l'on choisit dans l'admin**, Réglages > Apparence. Ordre décidé : d'abord le mécanisme et le modèle de boutique, puis l'accueil.

## Règles communes

- Un modèle change la **présentation** d'une page, jamais ses données, ses routes, le panier ni le paiement. « Classique » reste le modèle par défaut.
- Réglages `storefront_template_home` et `storefront_template_shop` (groupe `appearance`). Une valeur inconnue retombe sur « classique ». Un administrateur connecté peut prévisualiser avec `?template=beauty` sans enregistrer.
- Le modèle « Glowing Beauty » a sa **propre identité** (décidée) : palette brune, titres à largeur normale. Elle est portée par la classe `.tpl-beauty` (jetons de couleur redéfinis dans la page, en-tête et pied de page inchangés). Les polices manuscrites des maquettes viendront avec l'accueil.
- On reprend la **mise en page** des maquettes, pas leurs visuels : les photos et produits des captures sont de marques tierces. Pas de blog (décidé : omis).

## Boutique (construite)

Bandeau de titre avec les rayons et leur nombre de produits en vente (un rayon vide n'est pas proposé) ; colonne de filtres (prix, contenance lue des variantes, promotion, épuisés) et « produits les mieux notés » (avis approuvés seulement) ; barre d'outils : produits par page (9, 12, 18, 24), colonnes (2, 3, 4) et tri avec envoi automatique ; grille de cartes avec badges calculés (HOT, NOUVEAU sur 30 jours, -N %, ÉPUISÉ), prix net, « Ajouter » et favoris ; pagination existante. Toutes les valeurs de la requête inconnues sont ignorées.

## Accueil (à construire)

Sections de la maquette, mobile d'abord :

1. **Hero : un slider** (grand visuel en pleine largeur avec flèches, points et pause), alimenté par les bannières de l'admin puis complété par des produits en vedette, comme l'accueil actuel.
2. **Trois cartes de rayons avec photo** (photo du premier produit en vente du rayon, ou image téléversée).
3. **Meilleures ventes** et **produits en vedette** : titre à gauche, trois produits à droite.
4. **Comparaison avant/après** avec trois atouts (deux images et le texte en réglages).
5. **Offre avec compte à rebours** (date de fin et texte en réglages ; la section disparaît sans date).
6. Pas de blog.

Données à ajouter côté réglages : images et textes de l'avant/après, date de fin et texte de l'offre. Tout en français et en anglais, avec tests PHPUnit et Playwright.
