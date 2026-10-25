# Fiche produit : informations détaillées (onglets)

Demande du propriétaire (2026-10-25) : sur la fiche produit, ajouter une section **Utilisation, Ingrédients, Précautions d'emploi, Conditions de conservation** avec son contenu, sur le modèle de la fiche d'un site de référence (`images/pages/ARVEA-NATURE-…png`), avec le design de Glowing (`design-system/glowing-cosmetics/MASTER.md`, étape 3 de [storefront-redesign.md](storefront-redesign.md)). Le contenu de ce site sert de **repère de structure et de ton** (permission du propriétaire) : les textes de Glowing sont écrits pour ses propres produits, jamais recopiés.

## Ce que montre la référence

Sous la photo et le prix, une barre d'onglets (Utilisation · Ingrédients · Principales allégations · Précautions d'emploi · Conditions de conservation), un panneau par onglet. Ingrédients : la liste complète du produit en majuscules, puis quelques **ingrédients clés** dépliables, chacun avec ses bénéfices (« Hydratant / Nourrissant / Apaisant »). Précautions et conservation : deux ou trois phrases courtes. Les « principales allégations » sont hors périmètre (non demandées).

## Ce qui est construit

- **Données** (produit) : `how_to_use` (Utilisation, existait), `ingredients` (liste complète, existait), `key_ingredients` (nouveau), `precautions` (nouveau), `storage_conditions` (nouveau). Tous traduisibles (`translations:sync`). Saisis dans l'admin, fiche produit (création et modification).
- **Format des ingrédients clés** : une ligne par ingrédient, `Nom : bénéfice / bénéfice / bénéfice`.
- **Affichage** : composant `<x-storefront.product-info>`. Onglets à plat (filet, soulignement vert sur l'onglet actif, étiquettes en capitales), sans ombre. Seuls les onglets qui ont du contenu apparaissent ; aucun contenu, aucune section.
- **Ingrédients** : la liste à gauche, les ingrédients clés dépliables (`<details>`, sans script) à droite ; sur téléphone, l'un sous l'autre.
- **Accessibilité** : motif ARIA des onglets (`tablist`, `tab`, `tabpanel`), flèches, Début et Fin au clavier, zones tactiles de 44 px, onglets qui défilent horizontalement sur petit écran. **Sans JavaScript**, tous les panneaux s'affichent à la suite, chacun avec son titre.
- **Palette** : jetons du design system ; avec le modèle « Glowing Beauty », la section prend la palette brune comme le reste de la page.

## Contenu

Les mentions de précautions et de conservation reprennent des formulations courantes des cosmétiques (usage externe, éviter les yeux, hors de portée des enfants, abri de la chaleur). Les listes d'ingrédients des produits de démonstration sont **illustratives** et doivent être remplacées par la liste INCI réelle de chaque produit avant toute vente : une composition erronée est une information de sécurité fausse.
