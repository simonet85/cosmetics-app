# Design system : Glowing Cosmetics

Source de vérité visuelle de la boutique. Une page peut le surcharger dans `design-system/glowing-cosmetics/pages/<page>.md` (la règle de la page l'emporte). Faits sur le produit : `PRODUCT.md`. Plan des pages : `specs/storefront-redesign.md`.

**Direction retenue (version 2)** : *la pharmacie dermo-cosmétique*, attribuée par le tirage d'Impeccable (seed `60c08d599c91140b`) et confirmée par le propriétaire. Cette version remplace la première (ivoire, serif Playfair, or), que l'outil classait lui-même parmi les looks par défaut des interfaces générées.

Idée : chaque produit se lit comme l'étiquette d'un flacon de pharmacie, nom, contenance, composition, prix net, avant d'être un décor. Confiance, lisibilité du prix, mobile d'abord.

## Ce qui est construit

- Jetons et styles de base : `resources/css/storefront.css` (point d'entrée Vite séparé, scanné uniquement sur `resources/views/components`, `resources/views/storefront` et `resources/js/storefront.js`). Les anciennes pages gardent `resources/css/app.css`.
- Mise en page : `<x-layouts.storefront title="" description="">` ; en-tête et pied de page : `<x-storefront.header />`, `<x-storefront.footer />` (données lues en base par `App\View\Composers\StorefrontComposer`).
- Composants : `<x-ui.button>`, `<x-ui.price>`, `<x-ui.badge>`, `<x-ui.field>`, `<x-ui.icon>`, `<x-ui.section-heading>`, `<x-ui.empty-state>`.
- Comportement : `resources/js/storefront.js` (JavaScript natif) : tiroirs `<dialog>`, ajout au panier sans rechargement, compteurs, apparition au défilement, notifications.
- Format de prix : `format_fcfa()` (francs entiers, espaces insécables). Moyens de paiement affichés : `App\Support\PaymentMethods` (réglages du site).

## Couleurs (jetons Tailwind `@theme`)

Les composants n'utilisent **jamais** une valeur hexadécimale brute. Stratégie : **engagée** (un vert plein couvre de larges aplats), sur fond blanc.

| Jeton | Valeur | Rôle | Contraste |
|---|---|---|---|
| `ground` | `#FFFFFF` | fond de page, rayonnage | |
| `mint` | `#E3F0E8` | bandeaux, fonds d'étiquette | ink 14,3:1 |
| `ink` | `#13201A` | texte, pied de page | 16,8:1 sur blanc |
| `ink-muted` | `#4A5750` | légendes, aides | 7,6:1 sur blanc |
| `green` | `#0B7A4B` | aplats, bouton principal, bandeau d'annonce | 5,4:1 avec texte blanc |
| `green-deep` | `#075C37` | liens, survol, anneau de focus | 8,1:1 avec texte blanc |
| `sage` | `#5A7C6F` | vert de la marque, appoint décoratif | 4,6:1 (grand texte et décor seulement) |
| `line` | `#D5DED8` | filets (décoratifs, 1,4:1) | |
| `field` | `#7A8A81` | bordure des champs et cases | 3,6:1 (≥ 3:1) |
| `ticket` | `#FBD38D` | étiquette de prix (promo) | ink 11,9:1 |
| `amber` | `#9A4B06` | stock faible (texte) | 6,2:1 |
| `danger` | `#B42318` | erreurs, rupture (avec icône et texte) | 6,6:1 |

Interdits : la **croix verte** (signe réservé aux pharmacies), les dégradés, l'information portée par la seule couleur. Mode sombre : non prévu au lancement.

## Typographie

- **Archivo Variable**, auto-hébergée (`@fontsource-variable/archivo`, fichier `wdth` : poids 100–900 et largeur 62–125 %). Un seul visage pour tout.
- **Titres** : largeur 75 % (condensée), graisse 700, interligne 1,05, `text-wrap: balance`. **Étiquettes** (`.label-caps`) : condensé 75 %, 600, majuscules, interlettrage 0,06 em, 13 px. **Corps** : largeur 100 %, 16 px minimum, interligne 1,6.
- **Prix** : `.tnum` (chiffres tabulaires), condensé, gras ; l'unité « FCFA » plus petite à côté. Format `12 500 FCFA`, sans décimales.
- Pas d'« eyebrow » au-dessus des titres, pas de numérotation de sections.

## Forme et relief

- Angles presque droits (2 à 4 px), filets de 1 px, **aucune ombre** sur les composants (l'élévation n'existe que pour les notifications).
- Échelle d'espacement de 4 px ; sections espacées de 64 à 96 px sur ordinateur, 48 px sur mobile. Conteneur `.wrap` (1280 px, marges 16/24/32 px).
- Points de rupture : 360 (minimum), 640, 768, 1024, 1280. Mobile d'abord.
- Zones tactiles ≥ 44 px (boutons 48 px).

## Mouvement

Un seul **moment écrit** : le *ticket de prix* du produit en vedette sort de son étiquette, une fois, à l'arrivée à l'écran (`clip-path`, 650 ms). Le reste est discret : apparition au défilement (16 px et fondu, 450 ms, décalage de 40 ms), tiroirs 250 ms. Courbe `--ease-out-expo`. Les contenus sont **visibles par défaut** (le masquage ne s'applique que si JavaScript tourne, `html.js`). Tout est désactivé sous `prefers-reduced-motion`.

## Surfaces du navigateur

Sélection (vert/blanc), curseur de saisie (vert), barre de défilement (`scrollbar-color`), anneau de focus 3 px `green-deep` (blanc sur les aplats sombres), décalage de soulignement des liens : tous aux couleurs du monde.

## Composants et règles

- **Bouton** : 48 px, primaire (vert), secondaire (contour encre), inverse (sur aplat vert), texte. Un seul bouton primaire par écran.
- **Prix** : toujours lu côté serveur (`Product::final_price`, `CartService`). Remise = ancien prix barré **et** annoncé (« au lieu de »), jamais la couleur seule.
- **Champ** : libellé visible au-dessus, 48 px, erreur sous le champ liée par `aria-describedby`, aide persistante.
- **Pastille** : texte court en majuscules condensées, la couleur ne fait que soutenir.
- **Icônes** : un seul jeu dessiné, trait 1,75, décoratives par défaut (`aria-hidden`), le contrôle porte le nom accessible.
- **Navigation** : en-tête collant, tiroir `<dialog>` sur mobile (focus piégé par le navigateur), page courante en `aria-current`, lien « Aller au contenu ».
- **Pas de lien mort** : tout lien du pied de page mène à une vraie page ; pas de réseaux sociaux tant que les adresses réelles n'existent pas.
- **Aucune preuve inventée** : avis, labels, délais viennent de données réelles ou ne s'affichent pas.

## Accessibilité (obligatoire)

WCAG 2.2 AA : contrastes calculés, focus toujours visible, ordre de tabulation logique, titres sans saut, `lang="fr"`, un `h1` par page, alternative clavier partout, `prefers-reduced-motion` respecté.

## Performance

Vite, CSS limité aux vues redessinées, une police variable (le navigateur ne télécharge que le sous-ensemble latin, ≈ 90 Ko), aucun jQuery, aucune bibliothèque de carrousel, images avec dimensions déclarées et chargement différé sous la ligne de flottaison.

## À éviter

Ivoire et serif de prestige (le look par défaut écarté), rangées de cartes identiques comme structure de page, hero métrique, bordures gauches colorées, ombres décoratives, verre flou, texte dégradé, emoji comme icônes, valeurs de couleur en dur, prix ou stocks écrits dans les vues, carrousel automatique sans pause.
