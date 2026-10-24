# Design system : Glowing Cosmetics

Source de vérité visuelle de la boutique. Document de départ pour les pages ; une page peut le surcharger dans `design-system/glowing-cosmetics/pages/<page>.md` (la règle de la page l'emporte, sinon ce fichier s'applique).

**Produit** : boutique en ligne de cosmétiques haut de gamme pour l'Afrique francophone (paiement MoneyFusion, livraison, paiement à la livraison). Clients surtout sur mobile, en français, prix en FCFA.
**Direction** : *éditorial doux*. Beaucoup d'espace, un titre en serif, des photos grandes, des animations lentes et discrètes. Luxe calme, pas de décor.

## Comment ce système a été établi

Généré avec UI UX Pro Max (`search.py`, domaines `--design-system`, `product`, `style`, `color`, avec les réglages `--variance 3 --motion 4 --density 3`), puis **filtré** : la sortie brute proposait un motif « Feature-Rich Showcase » et le style « Minimalism & Swiss » (pensés pour des outils SaaS), et la palette « Beauty/Spa » rose et lavande, étrangère à la marque.

| Retenu de la base | Écarté | Pourquoi |
|---|---|---|
| Playfair Display + Inter (« elegant, luxury, high-end e-commerce ») | Palette rose `#EC4899` / lavande | Ne ressemble pas à la marque, qui est verte |
| Crème + accent or + charbon (« Premium dark + gold accent ») | Néomorphisme et verre liquide (« Soft UI », « Liquid Glass ») | Contrastes faibles, coût de rendu, mauvais pour l'accessibilité |
| Page d'accueil « Hero-Centric + Social Proof » (beauté) | « Vibrant & Block-based », « Playful colors » (la base les déconseille elle-même) | Trop bruyant pour une marque premium |
| Motion « Stagger List » (300 à 450 ms) | GSAP | Dépendance inutile : CSS + `IntersectionObserver` suffisent |

Continuité avec l'existant : le vert sauge `#5A7C6F` du site actuel reste la couleur de marque.

## Couleurs (jetons sémantiques)

Les composants n'utilisent **jamais** une valeur hexadécimale brute : seulement ces jetons.

| Rôle | Jeton | Valeur | Usage | Contraste |
|---|---|---|---|---|
| Fond de page | `--color-bg` | `#FAF7F2` | fond général (ivoire) | |
| Surface | `--color-surface` | `#FFFFFF` | cartes, tiroirs, champs | |
| Surface douce | `--color-surface-soft` | `#F2EDE4` | bandeaux, blocs alternés | |
| Texte | `--color-text` | `#1F2421` | texte courant, titres | 14,8:1 sur le fond |
| Texte atténué | `--color-text-muted` | `#5B625E` | légendes, aides | 5,9:1 |
| Primaire | `--color-primary` | `#2F4A3F` | boutons principaux, pied de page | 9,7:1 avec texte blanc |
| Marque (sauge) | `--color-brand` | `#5A7C6F` | grands titres, éléments décoratifs, bouton secondaire plein | 4,6:1 avec texte blanc ; **pas** en petit texte sur le fond (4,3:1) |
| Sauge profonde | `--color-brand-strong` | `#3F5E52` | liens, texte de marque, survol | 7,2:1 avec texte blanc |
| Accent or | `--color-accent` | `#8A5A12` | prix promo, étiquettes, anneau de focus | 5,5:1 sur le fond |
| Bordure décorative | `--color-line` | `#E4DDD0` | séparateurs, cartes | décoratif seulement |
| Bordure de champ | `--color-field-border` | `#8B8478` | champs de formulaire, cases | ≥ 3:1 (élément interactif) |
| Succès | `--color-success` | `#2F6B4F` | confirmations (avec icône + texte) | |
| Erreur | `--color-danger` | `#B42318` | erreurs, ruptures (avec icône + texte) | 6,2:1 |
| Focus | `--color-ring` | `#8A5A12` | anneau 3 px, décalage 2 px | 5,5:1 |

Règles : jamais l'information par la couleur seule (icône ou texte avec chaque couleur d'état) ; un seul bouton principal par écran ; le mode sombre n'est pas prévu pour le lancement (à concevoir à part, pas à inverser).

## Typographie

- **Titres** : Playfair Display (graisses 500 à 600), pour `h1` à `h3` et les prix en vedette.
- **Texte et interface** : Inter (400, 500, 600).
- Polices **auto-hébergées** (paquets `@fontsource` via Vite), pas de CDN ; `font-display: swap` ; précharger seulement Inter 400 et Playfair 500.
- Échelle (mobile, puis ≥ 1024 px) : 12 · 14 · **16 (corps minimum)** · 18 · 24 · 32 (40) · 40 (56).
- Interligne du corps 1,6 ; longueur de ligne 35 à 60 caractères sur mobile, 60 à 75 sur ordinateur.
- Prix et quantités : chiffres tabulaires (`font-variant-numeric: tabular-nums`). Format : `12 500 FCFA` (espace insécable), sans décimales.
- Titres courts : `text-wrap: balance`.

## Espacement, formes, relief

- Échelle d'espacement de 4 px : 4 · 8 · 12 · 16 · 24 · 32 · 48 · 64 · 96. Densité **aérée** : sections espacées de 64 à 96 px sur ordinateur, 48 sur mobile.
- Conteneur max 1280 px ; marges latérales 16 px (mobile), 24 (tablette), 32 (ordinateur).
- Rayons : 6 px (champs, boutons), 12 px (cartes, tiroirs), 999 px (pastilles).
- Ombres : deux niveaux seulement, très douces (`0 1px 2px` et `0 12px 32px -12px`, teinte du texte à 8 à 14 %). Pas d'effet de verre ni de néomorphisme.
- Points de rupture : 360 (cible minimum), 640, 768, 1024, 1280. Conception **mobile d'abord**, vérifiée de 360 à 430 px.
- Couches (`z-index`) : 0 contenu · 10 en-tête collant · 20 tiroir panier · 30 modale · 40 notifications.

## Mouvement

Principe : le mouvement exprime une cause (je survole, j'ajoute, la page apparaît), jamais une décoration. Une à deux animations par vue au maximum.

| Jeton | Valeur | Usage |
|---|---|---|
| `--motion-fast` | 150 ms | survol, appui, bascule |
| `--motion-base` | 250 ms | ouverture de tiroir, changement d'état |
| `--motion-slow` | 450 ms | apparition au défilement, zoom d'image |
| `--ease-out` | `cubic-bezier(0.22, 1, 0.36, 1)` | entrée |
| `--ease-in` | `cubic-bezier(0.64, 0, 0.78, 0)` | sortie (60 à 70 % de la durée d'entrée) |

- N'animer que `transform` et `opacity` (jamais largeur, hauteur, `top`, `left`).
- Apparition au défilement : translation de 16 px + fondu, 450 ms, décalage de 40 ms entre éléments d'une grille (8 maximum par séquence), déclenchée une seule fois via `IntersectionObserver`.
- Image produit au survol : zoom `scale(1.03)` en 450 ms. Bouton : légère réduction à l'appui (`scale(0.98)`).
- Ajout au panier : l'icône du panier fait un bref rebond et le compteur est annoncé (`aria-live="polite"`), sans bloquer l'interface.
- Toutes les animations sont désactivées avec `@media (prefers-reduced-motion: reduce)` : l'état final s'affiche tout de suite.
- Interdits : carrousels qui défilent seuls sans bouton pause, parallaxe forte, animations qui bloquent la saisie, compte à rebours décoratif.

## Composants

**Boutons.** Hauteur 48 px (zone tactile ≥ 44 px, ≥ 8 px entre deux zones). *Principal* : fond `--color-primary`, texte blanc. *Secondaire* : contour `--color-primary`. *Texte* : sauge profonde, souligné au survol. État de chargement : bouton désactivé + indicateur, texte conservé. Focus visible : anneau 3 px `--color-ring`.

**Carte produit.** Photo au ratio fixe 4:5 (aucun décalage de mise en page), nom, prix (prix promo en or + ancien prix barré, jamais seulement la couleur), pastilles *Nouveau* / *-15 %* / *Rupture*, bouton « Ajouter » d'au moins 44 px, note en étoiles avec le nombre d'avis. Toute la carte est un lien ; le bouton reste focalisable séparément.

**Prix et stock.** Les prix, remises, stocks et frais viennent **toujours** de la base de données via le serveur (`CartService`, `Product::final_price`), jamais écrits dans les vues. Stock : « En stock », « Plus que N » (quand N ≤ 5), « Rupture » avec icône.

**Filtres du catalogue.** Pastilles (catégorie, tag) et tri ; elles agissent sur les données réelles (requête serveur, URL partageable, état conservé au retour). Le nombre de résultats est annoncé. Sur mobile : tiroir « Filtrer » avec bouton « Voir N produits ».

**Panier.** Page et tiroir : lignes avec photo, variante, bouton de quantité (− / + de 44 px, saisie clavier), suppression avec annulation possible, sous-total, livraison et total recalculés par le serveur à chaque changement. Une ligne devenue indisponible est signalée au lieu de disparaître en silence.

**Formulaires.** Libellé visible au-dessus de chaque champ (jamais le seul texte indicatif), champ ≥ 44 px, `autocomplete` et `inputmode` adaptés (`tel`, `email`), erreur sous le champ avec `aria-describedby`, résumé d'erreurs focalisé en haut après un envoi raté, texte d'aide durable pour les champs complexes. Téléphone : format local ivoirien en exemple.

**Navigation.** En-tête collant (logo, catégories, recherche, compte, panier avec compteur), tiroir de navigation sur mobile (jamais d'icône sans texte pour la navigation principale), page courante repérée, fil d'Ariane dès 3 niveaux, lien « Aller au contenu » en premier.

**Notifications.** Zone `aria-live="polite"` qui ne prend pas le focus, disparition en 4 à 5 s, message avec cause et action de reprise. Pas d'emoji : icônes SVG d'un seul jeu (Phosphor, trait de 1,5 px).

**États vides et chargement.** Squelettes (pas de roue seule au-delà d'une seconde), états vides utiles (panier vide : lien vers la boutique ; recherche sans résultat : suggestions), erreurs avec bouton « Réessayer ».

## Images et performance

Formats WebP/AVIF avec `srcset`/`sizes`, `width` et `height` déclarés, `loading="lazy"` sous la ligne de flottaison, image d'en-tête chargée en priorité, texte alternatif descriptif pour les photos de produit (décoratif = `alt=""`). Budget : aucun défilement horizontal, pas de décalage de mise en page (CLS < 0,1).

## Accessibilité (obligatoire)

Contraste ≥ 4,5:1 (texte) et ≥ 3:1 (éléments d'interface), focus toujours visible et jamais masqué par un en-tête collant, ordre de tabulation = ordre visuel, titres sans saut de niveau, zones tactiles ≥ 44 px, alternative clavier à tout glisser-déposer, `prefers-reduced-motion` respecté, `lang="fr"`, un seul `h1` par page.

## Correspondance technique (Laravel, Blade, Tailwind v4)

- Jetons déclarés une fois dans `resources/css/app.css` avec `@theme { --color-bg: …; … }` : Tailwind génère les utilitaires (`bg-bg`, `text-muted`, `ring-ring`…).
- Composants Blade réutilisables dans `resources/views/components/` (`button`, `product-card`, `price`, `field`, `badge`, `quantity-stepper`, `section-heading`, `empty-state`).
- JavaScript natif uniquement (pas de jQuery, pas d'Alpine, pas de Livewire : voir `specs/tech-stack.md`) : apparition au défilement, tiroir, quantité, filtres, vérification du paiement.
- Notifications par une petite bibliothèque sans dépendance ou un composant maison, au choix de la phase 6.

## À éviter

Néomorphisme, verre liquide, dégradés violets ou roses, emoji comme icônes, ombres arbitraires, valeurs de couleur en dur dans les composants, plusieurs boutons principaux sur un même écran, carrousel automatique sans pause, prix ou stocks écrits dans les vues.
