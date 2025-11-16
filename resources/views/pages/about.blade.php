@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">À propos de nous</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">À propos</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    {{-- Our Story Section --}}
    <div class="max-w-4xl mx-auto mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Notre histoire</h2>
        <div class="prose prose-lg max-w-none text-gray-700">
            <p class="mb-4">
                Bienvenue chez <strong>GLOWING</strong>, votre destination beauté de confiance. Fondée avec la passion de révéler la beauté naturelle de chacun, nous nous engageons à offrir des produits cosmétiques de haute qualité qui respectent votre peau et l'environnement.
            </p>
            <p class="mb-4">
                Depuis notre création, nous avons pour mission de démocratiser l'accès à des produits de beauté premium, formulés avec des ingrédients naturels et respectueux. Chaque produit de notre collection est soigneusement sélectionné pour garantir efficacité, sécurité et plaisir d'utilisation.
            </p>
            <p>
                Nous croyons que la beauté commence par prendre soin de soi, et c'est pourquoi nous mettons un point d'honneur à vous accompagner dans votre routine beauté avec des conseils personnalisés et un service client exceptionnel.
            </p>
        </div>
    </div>

    {{-- Our Values Section --}}
    <div class="bg-gray-50 rounded-lg p-12 mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center">Nos valeurs</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#5a7c6f] rounded-full mb-4">
                    <i class="fas fa-leaf text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Naturel</h3>
                <p class="text-gray-700">
                    Nous privilégions des formules à base d'ingrédients naturels et biologiques, sans substances nocives pour votre peau.
                </p>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#5a7c6f] rounded-full mb-4">
                    <i class="fas fa-award text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Qualité</h3>
                <p class="text-gray-700">
                    Chaque produit est testé et certifié pour garantir les plus hauts standards de qualité et d'efficacité.
                </p>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#5a7c6f] rounded-full mb-4">
                    <i class="fas fa-heart text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Engagement</h3>
                <p class="text-gray-700">
                    Nous nous engageons pour un monde plus durable avec des emballages recyclables et une démarche éco-responsable.
                </p>
            </div>
        </div>
    </div>

    {{-- Why Choose Us Section --}}
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center">Pourquoi nous choisir?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            <div class="flex items-start gap-4 bg-white p-6 rounded-lg shadow-sm">
                <i class="fas fa-shipping-fast text-3xl text-[#5a7c6f] flex-shrink-0 mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Livraison gratuite</h3>
                    <p class="text-gray-700">Livraison offerte dès 50$ d'achat partout au Canada</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-white p-6 rounded-lg shadow-sm">
                <i class="fas fa-undo text-3xl text-[#5a7c6f] flex-shrink-0 mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Retours faciles</h3>
                    <p class="text-gray-700">30 jours pour changer d'avis, retour gratuit et sans tracas</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-white p-6 rounded-lg shadow-sm">
                <i class="fas fa-lock text-3xl text-[#5a7c6f] flex-shrink-0 mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Paiement sécurisé</h3>
                    <p class="text-gray-700">Transactions 100% sécurisées avec cryptage SSL</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-white p-6 rounded-lg shadow-sm">
                <i class="fas fa-headset text-3xl text-[#5a7c6f] flex-shrink-0 mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Support client</h3>
                    <p class="text-gray-700">Une équipe dédiée disponible 7j/7 pour vous accompagner</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Call to Action --}}
    <div class="text-center bg-gray-900 text-white rounded-lg p-12">
        <h2 class="text-3xl font-bold mb-4">Prêt à découvrir nos produits?</h2>
        <p class="text-xl text-gray-300 mb-8">Explorez notre collection et trouvez les produits parfaits pour vous</p>
        <a href="{{ route('shop.index') }}" class="inline-block bg-white text-gray-900 font-semibold px-8 py-4 rounded hover:bg-gray-100 transition-colors">
            Découvrir la boutique
        </a>
    </div>

</div>

@endsection
