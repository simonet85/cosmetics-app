@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Questions fréquentes</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">FAQ</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    <div class="max-w-4xl mx-auto">

        <p class="text-lg text-gray-700 text-center mb-12">
            Vous avez des questions? Consultez notre FAQ pour trouver rapidement les réponses aux questions les plus fréquentes.
        </p>

        {{-- FAQ Categories --}}
        <div class="space-y-8">

            {{-- Commandes & Livraison --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-shipping-fast text-[#5a7c6f] mr-3"></i>
                    Commandes & Livraison
                </h2>

                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Comment puis-je suivre ma commande?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Une fois votre commande expédiée, vous recevrez un email avec un numéro de suivi. Vous pouvez également suivre votre commande depuis votre compte en ligne dans la section "Mes commandes".
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Quels sont les frais de livraison?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                La livraison est gratuite pour toutes les commandes de plus de 50$. Pour les commandes inférieures, les frais de livraison standard sont de 10$.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Quels sont les délais de livraison?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Les commandes sont généralement traitées dans un délai de 1 à 2 jours ouvrables. La livraison standard prend entre 5 et 7 jours ouvrables. La livraison express (2-3 jours) est également disponible.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Retours & Échanges --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-undo text-[#5a7c6f] mr-3"></i>
                    Retours & Échanges
                </h2>

                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Quelle est votre politique de retour?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Vous disposez de 30 jours à compter de la réception de votre commande pour retourner un produit. Les articles doivent être non utilisés et dans leur emballage d'origine. Les frais de retour sont gratuits.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Comment effectuer un retour?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Pour effectuer un retour, connectez-vous à votre compte et accédez à la section "Mes commandes". Sélectionnez la commande concernée et cliquez sur "Retourner un article". Suivez ensuite les instructions pour imprimer votre étiquette de retour.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Quand serais-je remboursé?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Une fois que nous aurons reçu et inspecté votre retour, votre remboursement sera traité dans un délai de 5 à 7 jours ouvrables. Le remboursement sera crédité sur votre mode de paiement d'origine.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Produits --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-box text-[#5a7c6f] mr-3"></i>
                    Produits
                </h2>

                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Vos produits sont-ils testés sur les animaux?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Non, tous nos produits sont cruelty-free. Nous ne testons pas nos produits sur les animaux et nous ne travaillons qu'avec des marques partageant cette même valeur.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Les produits conviennent-ils aux peaux sensibles?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                La plupart de nos produits sont formulés pour convenir à tous les types de peau, y compris les peaux sensibles. Nous indiquons clairement sur chaque fiche produit le type de peau recommandé et les ingrédients utilisés.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Proposez-vous des échantillons?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Oui! Des échantillons gratuits sont offerts avec chaque commande. Vous pouvez également les sélectionner lors de votre passage en caisse.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Paiement & Sécurité --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-lock text-[#5a7c6f] mr-3"></i>
                    Paiement & Sécurité
                </h2>

                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Quels modes de paiement acceptez-vous?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Nous acceptons les cartes de crédit (Visa, MasterCard, American Express), PayPal et les virements bancaires. Tous les paiements sont sécurisés et cryptés.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg">
                        <button class="faq-question w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-50">
                            <span class="font-semibold text-gray-900">Mes informations sont-elles sécurisées?</span>
                            <i class="fas fa-chevron-down text-gray-600"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-4">
                            <p class="text-gray-700">
                                Oui, absolument. Notre site utilise un cryptage SSL pour protéger toutes vos informations personnelles et bancaires. Nous ne stockons jamais vos informations de carte de crédit sur nos serveurs.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Contact Section --}}
        <div class="mt-16 bg-gray-50 rounded-lg p-8 text-center">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">Vous ne trouvez pas la réponse à votre question?</h3>
            <p class="text-gray-700 mb-6">Notre équipe est là pour vous aider!</p>
            <a href="{{ route('pages.contact') }}" class="btn btn-primary">
                <i class="fas fa-envelope mr-2"></i>Contactez-nous
            </a>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
// FAQ Accordion functionality
document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', function() {
        const answer = this.nextElementSibling;
        const icon = this.querySelector('i');

        // Close all other answers
        document.querySelectorAll('.faq-answer').forEach(item => {
            if (item !== answer) {
                item.classList.add('hidden');
            }
        });

        // Reset all other icons
        document.querySelectorAll('.faq-question i').forEach(item => {
            if (item !== icon) {
                item.classList.remove('fa-chevron-up');
                item.classList.add('fa-chevron-down');
            }
        });

        // Toggle current answer
        answer.classList.toggle('hidden');

        // Toggle icon
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
    });
});
</script>
@endpush
