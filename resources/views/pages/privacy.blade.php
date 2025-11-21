@extends('layouts.app')

@section('title', 'Politique de confidentialité - Glowing Cosmetics')

@section('content')

{{-- Breadcrumb --}}
<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Politique de confidentialité</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="max-w-4xl mx-auto">

        <h1 class="text-4xl font-bold text-gray-900 mb-6">Politique de confidentialité</h1>

        <p class="text-gray-600 mb-8">
            Dernière mise à jour : 21 novembre 2025
        </p>

        <div class="prose prose-gray max-w-none">

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Introduction</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Bienvenue sur Glowing Cosmetics. Nous nous engageons à protéger votre vie privée et à garantir la sécurité de vos informations personnelles. Cette politique de confidentialité décrit comment nous collectons, utilisons, stockons et protégeons vos données lorsque vous utilisez notre site web <a href="https://klab-consulting.com" class="text-[#5a7c6f] hover:underline">klab-consulting.com</a>.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    En utilisant notre site, vous acceptez les pratiques décrites dans cette politique. Si vous n'acceptez pas ces termes, veuillez ne pas utiliser notre site.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Informations que nous collectons</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.1 Informations que vous nous fournissez</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous collectons les informations que vous nous fournissez directement lorsque vous :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Créez un compte (prénom, nom, email, mot de passe)</li>
                    <li>Passez une commande (adresse de livraison, numéro de téléphone, ville, quartier)</li>
                    <li>Vous connectez via Google ou Facebook (nom, email, photo de profil)</li>
                    <li>Nous contactez (nom, email, message)</li>
                    <li>Laissez un avis sur un produit</li>
                    <li>Vous abonnez à notre newsletter</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.2 Informations collectées automatiquement</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Lorsque vous visitez notre site, nous pouvons collecter automatiquement :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Adresse IP</li>
                    <li>Type de navigateur et système d'exploitation</li>
                    <li>Pages visitées et durée de visite</li>
                    <li>Source de référence (comment vous êtes arrivé sur notre site)</li>
                    <li>Données de cookies et technologies similaires</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.3 Informations de paiement</h3>
                <p class="text-gray-700 leading-relaxed">
                    Les informations de paiement sont traitées de manière sécurisée par notre prestataire de paiement MoneyFusion. Nous ne stockons pas vos informations de carte bancaire sur nos serveurs.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Comment nous utilisons vos informations</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous utilisons vos informations pour :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Traiter et livrer vos commandes</li>
                    <li>Gérer votre compte et vous fournir un service client</li>
                    <li>Vous envoyer des confirmations de commande et des mises à jour de livraison</li>
                    <li>Répondre à vos questions et demandes</li>
                    <li>Améliorer notre site web et nos services</li>
                    <li>Vous envoyer des offres promotionnelles (si vous y avez consenti)</li>
                    <li>Détecter et prévenir la fraude</li>
                    <li>Respecter nos obligations légales</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Partage de vos informations</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous ne vendons jamais vos informations personnelles. Nous pouvons partager vos informations avec :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li><strong>Prestataires de services :</strong> Sociétés qui nous aident à exploiter notre site (hébergement, paiement, livraison, email)</li>
                    <li><strong>Partenaires de paiement :</strong> MoneyFusion pour le traitement sécurisé des paiements</li>
                    <li><strong>Services d'authentification :</strong> Google et Facebook lorsque vous utilisez la connexion sociale</li>
                    <li><strong>Autorités légales :</strong> Si requis par la loi ou pour protéger nos droits</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Cookies et technologies similaires</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous utilisons des cookies et technologies similaires pour :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Mémoriser vos préférences et paramètres</li>
                    <li>Maintenir votre session de connexion</li>
                    <li>Analyser l'utilisation de notre site</li>
                    <li>Personnaliser votre expérience</li>
                </ul>
                <p class="text-gray-700 leading-relaxed">
                    Vous pouvez gérer les cookies via les paramètres de votre navigateur.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Sécurité de vos données</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos informations personnelles :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Chiffrement SSL/HTTPS pour toutes les transmissions de données</li>
                    <li>Hashage sécurisé des mots de passe (Bcrypt)</li>
                    <li>Serveurs sécurisés avec accès restreint</li>
                    <li>Surveillance régulière pour détecter les vulnérabilités</li>
                    <li>Sauvegardes régulières des données</li>
                </ul>
                <p class="text-gray-700 leading-relaxed">
                    Cependant, aucune méthode de transmission sur Internet n'est 100% sécurisée. Nous nous efforçons d'utiliser des moyens commercialement acceptables pour protéger vos données.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Vos droits</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Vous avez le droit de :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li><strong>Accéder</strong> à vos données personnelles</li>
                    <li><strong>Rectifier</strong> vos données inexactes</li>
                    <li><strong>Supprimer</strong> vos données (droit à l'oubli)</li>
                    <li><strong>Exporter</strong> vos données dans un format lisible</li>
                    <li><strong>Vous opposer</strong> au traitement de vos données</li>
                    <li><strong>Retirer votre consentement</strong> à tout moment</li>
                    <li><strong>Vous désabonner</strong> de nos communications marketing</li>
                </ul>
                <p class="text-gray-700 leading-relaxed">
                    Pour exercer ces droits, contactez-nous à <a href="mailto:privacy@klab-consulting.com" class="text-[#5a7c6f] hover:underline">privacy@klab-consulting.com</a>
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Conservation des données</h2>
                <p class="text-gray-700 leading-relaxed">
                    Nous conservons vos informations personnelles aussi longtemps que nécessaire pour fournir nos services et respecter nos obligations légales. Les comptes inactifs pendant plus de 3 ans peuvent être supprimés après notification.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Données des mineurs</h2>
                <p class="text-gray-700 leading-relaxed">
                    Notre site n'est pas destiné aux personnes de moins de 18 ans. Nous ne collectons pas sciemment d'informations personnelles auprès de mineurs. Si vous êtes parent et que vous pensez que votre enfant nous a fourni des informations personnelles, contactez-nous.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Connexion sociale (Google et Facebook)</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Lorsque vous vous connectez via Google ou Facebook :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Nous recevons votre nom, email et photo de profil</li>
                    <li>Ces informations sont utilisées pour créer et gérer votre compte</li>
                    <li>Nous ne publions rien sur vos comptes sociaux sans votre permission</li>
                    <li>Vous pouvez dissocier votre compte social à tout moment</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Transferts internationaux de données</h2>
                <p class="text-gray-700 leading-relaxed">
                    Vos informations peuvent être transférées et stockées sur des serveurs situés en dehors de votre pays de résidence. Nous prenons des mesures appropriées pour garantir que vos données restent protégées conformément à cette politique.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Modifications de cette politique</h2>
                <p class="text-gray-700 leading-relaxed">
                    Nous pouvons mettre à jour cette politique de confidentialité de temps à autre. La version la plus récente sera toujours disponible sur cette page avec la date de dernière mise à jour. Les modifications importantes vous seront notifiées par email.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Contact</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Pour toute question concernant cette politique de confidentialité ou vos données personnelles, contactez-nous :
                </p>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <p class="text-gray-700"><strong>Glowing Cosmetics</strong></p>
                    <p class="text-gray-700">Email : <a href="mailto:privacy@klab-consulting.com" class="text-[#5a7c6f] hover:underline">privacy@klab-consulting.com</a></p>
                    <p class="text-gray-700">Site web : <a href="https://klab-consulting.com" class="text-[#5a7c6f] hover:underline">https://klab-consulting.com</a></p>
                    <p class="text-gray-700">Page de contact : <a href="{{ route('pages.contact') }}" class="text-[#5a7c6f] hover:underline">Nous contacter</a></p>
                </div>
            </section>

        </div>

        <div class="mt-12 p-6 bg-[#5a7c6f] bg-opacity-10 rounded-lg">
            <p class="text-gray-700 text-center">
                En utilisant notre site, vous confirmez avoir lu et accepté cette politique de confidentialité.
            </p>
        </div>

    </div>
</div>

@endsection
