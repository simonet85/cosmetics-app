<footer class="bg-gray-100 mt-20 py-14">
    <div class="container-custom">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            {{-- Company Info --}}
            <div>
                <a href="{{ route('home') }}" class="inline-block mb-6">
                    <span class="text-2xl font-bold text-gray-900">GLOWING</span>
                </a>
                <p class="text-gray-700 text-lg mb-6">
                    Cosmétiques premium pour la beauté naturelle et le bien-être. Découvrez notre collection exclusive.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="text-gray-900 hover:text-gray-600 transition text-xl"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-900 hover:text-gray-600 transition text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-900 hover:text-gray-600 transition text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-900 hover:text-gray-600 transition text-xl"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h3 class="text-lg font-semibold mb-6 text-gray-900">Liens Rapides</h3>
                <ul class="space-y-4">
                    <li><a href="{{ route('shop.index') }}" class="text-gray-700 hover:text-gray-900 transition">Boutique</a></li>
                    <li><a href="{{ route('pages.about') }}" class="text-gray-700 hover:text-gray-900 transition">À Propos</a></li>
                    <li><a href="{{ route('pages.contact') }}" class="text-gray-700 hover:text-gray-900 transition">Contact</a></li>
                    <li><a href="{{ route('pages.faq') }}" class="text-gray-700 hover:text-gray-900 transition">FAQ</a></li>
                </ul>
            </div>

            {{-- Customer Service --}}
            <div>
                <h3 class="text-lg font-semibold mb-6 text-gray-900">Service Client</h3>
                <ul class="space-y-4">
                    <li><a href="{{ route('account.dashboard') }}" class="text-gray-700 hover:text-gray-900 transition">Mon Compte</a></li>
                    <li><a href="{{ route('account.orders') }}" class="text-gray-700 hover:text-gray-900 transition">Suivre ma Commande</a></li>
                    <li><a href="#" class="text-gray-700 hover:text-gray-900 transition">Politique de Livraison</a></li>
                    <li><a href="#" class="text-gray-700 hover:text-gray-900 transition">Retours</a></li>
                </ul>
            </div>

            {{-- Newsletter --}}
            <div>
                <h3 class="text-lg font-semibold mb-6 text-gray-900">Newsletter</h3>
                <p class="text-gray-700 mb-6">Abonnez-vous pour recevoir des offres spéciales et des mises à jour</p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="email" name="email" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900" placeholder="Votre email" required>
                    <button type="submit" class="btn btn-primary whitespace-nowrap">S'abonner</button>
                </form>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="border-t border-gray-300 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-600 text-center md:text-left">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
            </p>
            <div class="flex gap-6">
                <a href="#" class="text-gray-600 hover:text-gray-900 transition">Politique de Confidentialité</a>
                <a href="#" class="text-gray-600 hover:text-gray-900 transition">Conditions Générales</a>
            </div>
        </div>
    </div>
</footer>
