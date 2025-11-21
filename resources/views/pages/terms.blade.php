@extends('layouts.app')

@section('title', 'Conditions d\'utilisation - Glowing Cosmetics')

@section('content')

{{-- Breadcrumb --}}
<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Conditions d'utilisation</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="max-w-4xl mx-auto">

        <h1 class="text-4xl font-bold text-gray-900 mb-6">Conditions d'utilisation</h1>

        <p class="text-gray-600 mb-8">
            Dernière mise à jour : 21 novembre 2025
        </p>

        <div class="prose prose-gray max-w-none">

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Acceptation des conditions</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Bienvenue sur Glowing Cosmetics. En accédant et en utilisant ce site web (<a href="https://klab-consulting.com" class="text-[#5a7c6f] hover:underline">klab-consulting.com</a>), vous acceptez d'être lié par ces conditions d'utilisation, toutes les lois et réglementations applicables.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Si vous n'acceptez pas l'une de ces conditions, vous n'êtes pas autorisé à utiliser ou accéder à ce site. Veuillez lire attentivement ces conditions avant d'utiliser notre site.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Utilisation du site</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.1 Licence d'utilisation</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Nous vous accordons une licence limitée, non exclusive, non transférable et révocable pour accéder et utiliser notre site à des fins personnelles et non commerciales.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">2.2 Restrictions d'utilisation</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Vous vous engagez à ne pas :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Utiliser le site de manière illégale ou frauduleuse</li>
                    <li>Violer les droits de propriété intellectuelle</li>
                    <li>Transmettre des virus, malwares ou codes malveillants</li>
                    <li>Collecter des informations sur d'autres utilisateurs</li>
                    <li>Interférer avec le fonctionnement du site</li>
                    <li>Créer de faux comptes ou usurper l'identité d'autrui</li>
                    <li>Utiliser des robots, scrapers ou autres moyens automatisés</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Comptes utilisateurs</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.1 Création de compte</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Pour effectuer des achats, vous devez créer un compte. Vous vous engagez à fournir des informations exactes, complètes et à jour. Vous êtes responsable de la confidentialité de votre mot de passe et de toutes les activités effectuées sous votre compte.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.2 Connexion sociale</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Vous pouvez créer un compte en utilisant votre compte Google ou Facebook. En utilisant cette option, vous autorisez ces plateformes à partager certaines informations avec nous (nom, email, photo de profil).
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">3.3 Suspension et résiliation</h3>
                <p class="text-gray-700 leading-relaxed">
                    Nous nous réservons le droit de suspendre ou de résilier votre compte en cas de violation de ces conditions, sans préavis ni responsabilité.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Commandes et paiements</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.1 Processus de commande</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Lorsque vous passez une commande, vous faites une offre d'achat. Nous nous réservons le droit d'accepter ou de refuser toute commande. La confirmation de commande constitue notre acceptation de votre offre.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.2 Prix et disponibilité</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Tous les prix sont affichés en Francs CFA (FCFA) et incluent la TVA le cas échéant. Nous nous efforçons de maintenir les prix à jour, mais nous nous réservons le droit de modifier les prix sans préavis. Les produits sont soumis à disponibilité.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">4.3 Méthodes de paiement</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Nous acceptons les paiements par :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>MoneyFusion (carte bancaire en ligne)</li>
                    <li>Paiement à la livraison (cash)</li>
                    <li>Virement bancaire</li>
                </ul>
                <p class="text-gray-700 leading-relaxed">
                    Tous les paiements sont sécurisés et protégés par cryptage SSL.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Livraison</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">5.1 Zones de livraison</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Nous livrons actuellement sur l'ensemble du territoire de la République Démocratique du Congo. Les frais et délais de livraison varient selon votre localisation.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">5.2 Délais de livraison</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Les délais de livraison indiqués sont estimatifs. Nous ne pouvons être tenus responsables des retards dus à des circonstances indépendantes de notre volonté.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">5.3 Réception de la commande</h3>
                <p class="text-gray-700 leading-relaxed">
                    Vous devez vérifier l'état de votre colis à la réception. Tout dommage apparent doit être signalé immédiatement au livreur et à notre service client.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Retours et remboursements</h2>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">6.1 Droit de rétractation</h3>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Vous disposez d'un délai de 14 jours à compter de la réception de votre commande pour exercer votre droit de rétractation, sans avoir à justifier de motifs.
                </p>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">6.2 Conditions de retour</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Pour être éligible au retour, le produit doit :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Être dans son emballage d'origine, non ouvert et non utilisé</li>
                    <li>Inclure tous les accessoires et documents</li>
                    <li>Être accompagné de la facture d'achat</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">6.3 Produits non retournables</h3>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Pour des raisons d'hygiène et de sécurité, les produits suivants ne peuvent être retournés :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Produits cosmétiques ouverts ou utilisés</li>
                    <li>Produits en promotion ou en solde (sauf défaut)</li>
                    <li>Produits personnalisés</li>
                </ul>

                <h3 class="text-xl font-semibold text-gray-900 mb-3">6.4 Remboursement</h3>
                <p class="text-gray-700 leading-relaxed">
                    Une fois le retour validé, nous procéderons au remboursement dans un délai de 14 jours, en utilisant le même moyen de paiement que celui utilisé pour la transaction initiale.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Propriété intellectuelle</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    Tout le contenu de ce site (textes, images, logos, vidéos, graphiques) est la propriété de Glowing Cosmetics ou de ses partenaires et est protégé par les lois sur la propriété intellectuelle.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Vous ne pouvez pas reproduire, distribuer, modifier ou créer des œuvres dérivées du contenu sans notre autorisation écrite préalable.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Avis et commentaires</h2>
                <p class="text-gray-700 leading-relaxed mb-4">
                    En publiant un avis ou un commentaire sur notre site, vous nous accordez une licence mondiale, non exclusive, gratuite et perpétuelle pour utiliser, reproduire et afficher ce contenu.
                </p>
                <p class="text-gray-700 leading-relaxed">
                    Vos avis doivent être honnêtes, respectueux et conformes à la loi. Nous nous réservons le droit de modérer ou supprimer tout contenu inapproprié.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Limitation de responsabilité</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Dans les limites autorisées par la loi :
                </p>
                <ul class="list-disc pl-6 mb-4 text-gray-700 space-y-2">
                    <li>Nous ne garantissons pas que le site sera exempt d'erreurs ou ininterrompu</li>
                    <li>Nous ne sommes pas responsables des dommages indirects, accessoires ou consécutifs</li>
                    <li>Notre responsabilité totale est limitée au montant payé pour le produit concerné</li>
                    <li>Nous ne sommes pas responsables des produits utilisés de manière incorrecte</li>
                </ul>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Protection des données</h2>
                <p class="text-gray-700 leading-relaxed">
                    Vos données personnelles sont traitées conformément à notre <a href="{{ route('privacy') }}" class="text-[#5a7c6f] hover:underline">Politique de confidentialité</a>. En utilisant notre site, vous consentez à la collecte et à l'utilisation de vos données comme décrit dans cette politique.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Liens externes</h2>
                <p class="text-gray-700 leading-relaxed">
                    Notre site peut contenir des liens vers des sites web tiers. Nous ne sommes pas responsables du contenu ou des pratiques de confidentialité de ces sites. Nous vous encourageons à lire leurs conditions d'utilisation.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Force majeure</h2>
                <p class="text-gray-700 leading-relaxed">
                    Nous ne serons pas tenus responsables de tout manquement à nos obligations résultant de circonstances indépendantes de notre volonté (catastrophes naturelles, guerres, grèves, pandémies, etc.).
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Modifications des conditions</h2>
                <p class="text-gray-700 leading-relaxed">
                    Nous nous réservons le droit de modifier ces conditions d'utilisation à tout moment. Les modifications entreront en vigueur dès leur publication sur cette page. Votre utilisation continue du site après les modifications constitue votre acceptation des nouvelles conditions.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">14. Loi applicable et juridiction</h2>
                <p class="text-gray-700 leading-relaxed">
                    Ces conditions sont régies par les lois de la République Démocratique du Congo. Tout litige sera soumis à la juridiction exclusive des tribunaux de Kinshasa.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">15. Divisibilité</h2>
                <p class="text-gray-700 leading-relaxed">
                    Si une disposition de ces conditions est jugée invalide ou inapplicable, les autres dispositions resteront en vigueur.
                </p>
            </section>

            <section class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">16. Contact</h2>
                <p class="text-gray-700 leading-relaxed mb-3">
                    Pour toute question concernant ces conditions d'utilisation, contactez-nous :
                </p>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <p class="text-gray-700"><strong>Glowing Cosmetics</strong></p>
                    <p class="text-gray-700">Email : <a href="mailto:support@klab-consulting.com" class="text-[#5a7c6f] hover:underline">support@klab-consulting.com</a></p>
                    <p class="text-gray-700">Site web : <a href="https://klab-consulting.com" class="text-[#5a7c6f] hover:underline">https://klab-consulting.com</a></p>
                    <p class="text-gray-700">Page de contact : <a href="{{ route('pages.contact') }}" class="text-[#5a7c6f] hover:underline">Nous contacter</a></p>
                </div>
            </section>

        </div>

        <div class="mt-12 p-6 bg-[#5a7c6f] bg-opacity-10 rounded-lg">
            <p class="text-gray-700 text-center">
                En utilisant notre site, vous confirmez avoir lu, compris et accepté ces conditions d'utilisation.
            </p>
        </div>

    </div>
</div>

@endsection
