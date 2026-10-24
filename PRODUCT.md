# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Acheteuses et acheteurs de cosmétiques en Côte d'Ivoire et en Afrique francophone, qui achètent surtout depuis un téléphone (visites de 360 à 430 px de large), en français, souvent sans compte client. Leur travail : repérer le bon produit, vérifier le prix et la disponibilité, commander et payer en confiance (mobile money, paiement à la livraison ou virement), puis suivre la commande. Il leur faut de la rassurance sur le paiement et la livraison, pas du jargon.

Autres personnes servies, confirmées par le projet : la gérante ou le gérant de la boutique, qui administre produits, commandes, coupons et bannières sans développeur (interface d'administration à part), et le développeur unique qui maintient le site.

## Product Purpose

Vendre en ligne des cosmétiques, avec une boutique que les clients locaux peuvent utiliser jusqu'au bout depuis leur téléphone : catalogue avec variantes, panier, commande invité, paiement MoneyFusion, paiement à la livraison, virement bancaire, confirmation et suivi. Succès : une commande payée sans abandon, et une gérante qui tient la boutique seule.

## Positioning

Confirmé par le propriétaire du projet : produits **naturels et sans cruauté**, **paiement local simple** (mobile money MoneyFusion, à la livraison, virement, sans compte obligatoire), **sélection pointue de style haut de gamme** plutôt qu'un grand rayon, et **livraison rapide en Côte d'Ivoire**. Ce sont des engagements à n'afficher que lorsqu'ils sont vrais et vérifiables (voir « Evidence on Hand »).

## Operating Context

- Langue : français (locale `fr`), prix en FCFA sans décimales, par exemple `12 500 FCFA`.
- Livraison gratuite à partir d'un seuil lu dans les réglages du site (50 000 FCFA par défaut) ; frais standard et taux de taxe également dans les réglages.
- Paiement : MoneyFusion (mobile money, confirmation par serveur, vérification de statut), paiement à la livraison, virement. Un modèle de paiement peut être désactivé dans l'administration.
- Hébergement mutualisé Hostinger (klab-consulting.com), déploiement manuel par SSH, base MySQL.
- Les prix, remises, stocks et frais viennent du serveur (jamais écrits dans les vues).

## Capabilities and Constraints

- Application Laravel (Blade) avec Tailwind CSS v4 et Vite ; JavaScript natif (pas de jQuery, ni Alpine, ni Livewire : décision actuelle de `specs/tech-stack.md`). Le serveur de production n'a pas Node : les ressources front sont construites en local puis envoyées.
- Fonctions existantes : catalogue avec filtres (catégories, tags) et variantes, fiche produit, avis, liste de souhaits, panier calculé côté serveur, commande invité ou connectée, pages de confirmation et de paiement en attente, compte client, administration complète, e-mails de commande.
- Contraintes d'accès : pages de confirmation réservées au propriétaire de la commande ou à un lien signé.
- Pas de données de carte bancaire dans l'application.
- Non décidé : mode sombre, version anglaise, application mobile native.

## Brand Commitments

- Nom : **Glowing Cosmetics** (affiché « GLOWING » dans l'en-tête). Vert sauge `#5A7C6F` déjà utilisé sur le site actuel.
- Logo : `public/images/others/logo-black-02.png`, `logo-white-02.png`, `logo-white.png`.
- Le design system retenu est dans `design-system/glowing-cosmetics/MASTER.md` ; il fait foi pour les décisions visuelles, ce fichier-ci ne contient que des faits sur le produit.

## Evidence on Hand

Confirmé par le propriétaire : le logo, de **vraies photos produit** et de **vrais avis clients** existent.

- Photos : `public/images/products/` (28 fichiers), `public/images/shop/` (13), `public/images/hero-slider/` (52), `public/images/banner/`, `public/images/header/`, `public/images/instagram/`, `public/images/blog/`. Le contenu exact de chaque dossier (photo réelle ou image d'exemple) est à vérifier fichier par fichier avant de les présenter comme réelles.
- Avis et témoignages : tables `reviews` et `testimonials` (modèles `Review`, `Testimonial`) ; la base de développement ne contient que des données de démonstration, la production contient les vraies données.
- Absences à ne pas combler en inventant : aucun chiffre de ventes, aucun prix ni délai de livraison autre que les réglages du site, aucune certification « bio » ou « sans cruauté » documentée dans le dépôt (à faire confirmer avant de l'afficher comme preuve).

## Product Principles

1. **Confiance avant esthétique** : chaque écran qui touche à l'argent dit clairement le prix final, la livraison et ce qui se passe ensuite.
2. **Le téléphone est l'écran principal** : tout parcours d'achat se fait au pouce, en français, sur une connexion moyenne.
3. **Le serveur est la source de vérité** : prix, stocks, frais et statuts ne sont jamais recopiés dans les vues.
4. **Pas de preuve inventée** : avis, labels, délais et chiffres viennent de données réelles ou ne sont pas affichés.
5. **Luxe calme** : sélection resserrée, photos de produits mises en avant, peu de bruit.

## Accessibility & Inclusion

Cible : WCAG 2.2 AA. Contrastes suffisants, navigation clavier complète, zones tactiles d'au moins 44 px, respect de `prefers-reduced-motion`, formulaires avec libellés visibles et erreurs liées aux champs. Public majoritairement mobile avec des connexions variables : légèreté des pages et images optimisées font partie de l'inclusion.
