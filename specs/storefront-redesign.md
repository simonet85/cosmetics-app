# Refonte de la boutique : plan des pages (à valider avant tout code)

Brief `shape` : ce document décrit **quoi** construire et **comment ça doit fonctionner**. Il ne choisit pas encore le monde visuel final (voir « Décisions ouvertes »). Faits produit : `PRODUCT.md`. Règles visuelles de départ : `design-system/glowing-cosmetics/MASTER.md`.

## Travail, public, résultat

- **Quoi** : refaire l'interface client de la boutique Laravel (Blade + Tailwind v4 + JavaScript natif), page par page, sans réécrire le backend qui fonctionne (panier calculé côté serveur, commandes, paiements, tests).
- **Qui** : acheteuses et acheteurs d'Afrique francophone, sur téléphone (360 à 430 px d'abord), souvent sans compte, qui doivent être rassurés sur le paiement et la livraison.
- **Réussite** : plus de commandes payées sans abandon ; une gérante qui n'a besoin de personne ; Lighthouse mobile ≥ 90 en performance et accessibilité sur les pages principales.
- **Hors périmètre** : l'administration, les e-mails (déjà refaits en phase 5, à harmoniser plus tard), la logique de paiement et de stock.

## Les pages, dans l'ordre de construction

Chaque page suit le cycle de la spec : `/impeccable craft`, puis `typeset`, `layout`, `colorize`, `animate`, `polish`, avec contrôle bureau, mobile et interactions avant de passer à la suivante.

| # | Page | Mode | Contenu réel et comportement |
|---|---|---|---|
| 0 | **Fondations** | | Jetons Tailwind (`@theme`), polices auto-hébergées, mise en page de base (en-tête collant, tiroir de navigation mobile, pied de page, lien « aller au contenu »), notifications accessibles, composants Blade : `button`, `field`, `price`, `badge`, `product-card`, `quantity-stepper`, `section-heading`, `empty-state`. JavaScript natif : apparition au défilement, tiroir, quantité. |
| 1 | **Accueil** | Persuade | Hero émotionnel (vraie photo), **les univers de la boutique lus dans les catégories réelles** (pas écrits en dur), sélection mise en avant (produits « en vedette » de la base), engagements vérifiables (livraison gratuite dès le seuil des réglages, paiements acceptés), avis réels, appel à l'action. |
| 2 | **Boutique (catalogue)** | Operate | Filtres sur données réelles : catégories, tags, fourchette de prix (min/max calculés en base), « en stock », tri ; URL partageable, état conservé au retour ; pagination ; résultat annoncé ; états vide et chargement ; tiroir « Filtrer » sur mobile. |
| 3 | **Fiche produit** | Operate | Galerie, variantes (prix et stock par variante), prix remisé et ancien prix, indicateur de stock réel, ajout au panier avec retour immédiat, onglets description / avis / livraison, produits liés, barre d'achat collante sur mobile, données structurées `Product`. |
| 4 | **Panier** (page + mini-panier) | Operate | Ajout, retrait avec annulation possible, quantités, variantes, sous-total, frais de livraison et seuil de gratuité (réglages), total recalculé par le serveur, ligne devenue indisponible signalée. Voir « Ajouts côté serveur ». |
| 5 | **Commande** | Operate | Une page, invité d'abord : coordonnées, adresse de livraison (ville, quartier), moyens de paiement activés dans les réglages (MoneyFusion, à la livraison, virement), récapitulatif, un seul bouton principal, protection contre le double envoi. |
| 6 | **Paiement** : en attente, réussi, échoué | Operate | Pages existantes (vérification automatique, bouton « vérifier ») remises au design ; messages clairs, reprise possible. |
| 7 | **Compte** | Operate | Connexion, inscription, tableau de bord, commandes et détail (suivi), profil, favoris. |
| 8 | **Pages d'information** | Read | À propos, contact (formulaire), FAQ, conditions, confidentialité. |
| 9 | **Transversal** | | SEO (balises par page, `sitemap.xml`, `robots.txt`, JSON-LD produit), images WebP/AVIF avec `srcset`, accessibilité WCAG 2.2 AA, mouvement réduit, performances. |

À la fin : `/impeccable polish` puis `/impeccable audit`, puis **parcours testés avec Playwright CLI** (navigation, filtres, ajout au panier, commande à la livraison, paiement en attente avec le simulateur MoneyFusion, formulaires, responsive 360 à 430 / 768 / 1440, animations avec et sans mouvement réduit).

## Ajouts côté serveur que la spec demande (à confirmer)

Ils ne sont **pas** faits aujourd'hui ; je les ajouterais avec des tests, dans les étapes citées :

1. **Promotions** (étape 4) : le modèle `Coupon` et l'administration existent, mais ni le panier ni la commande n'appliquent un code. À faire : saisie du code, validation côté serveur (dates, plafond, minimum, quota), remise dans les totaux et sur la commande.
2. **Panier conservé** (étape 4) : aujourd'hui le panier ne vit que dans la session. Proposition : visiteur = session (durée de vie normale) ; client connecté = enregistré en base (table `cart` existante) et **fusionné** à la connexion.
3. **Protection contre le double envoi** (étape 5) : aujourd'hui un double clic ou un rechargement peut créer deux commandes. À faire : jeton d'envoi unique par formulaire, vérifié et consommé côté serveur, bouton désactivé pendant l'envoi.
4. **Adresses** (étape 5) : la commande garde déjà une copie des adresses (nom, ville, quartier, téléphone) et des lignes (nom, SKU, prix, quantité au moment de l'achat) : c'est l'instantané demandé. Un carnet d'adresses pour les clients connectés serait un modèle `Address` en plus (optionnel).

## Contraintes

Laravel/Blade/Tailwind v4/Vite, **JavaScript natif** (ni jQuery, ni Alpine, ni Livewire : `specs/tech-stack.md`). Mobile d'abord. Pas de mode sombre au lancement. Le serveur de production n'a pas Node : `public/build` est construit en local et envoyé par `scripts/deploy.sh`. Les routes, noms de routes et contrôleurs existants sont conservés, de sorte que les tests actuels (212) restent valides.

## Décisions ouvertes

1. **Monde visuel.** Le design system de départ (ivoire, sauge, or, Playfair Display + Inter) vient de la base UI UX Pro Max. Impeccable le classe lui-même parmi les « looks par défaut » des interfaces générées : crème, serif de prestige, accent doré. La première page (accueil) passera donc par son tirage de directions (`concept-seed`) avant tout code ; ce qui est décidé à ce moment-là peut modifier `MASTER.md`.
2. **Hors outil de maquettes.** Pas de génération d'images ici : les directions sont présentées par fiches et croquis HTML, et les vraies photos du dépôt servent d'images (à vérifier une par une).
3. **Outils non installés** : SkillUI (à proposer seulement si vous voulez vous inspirer d'un site existant), navigateur de Playwright (téléchargement au premier test, taille annoncée avant).
