@extends('layouts.app')

@section('content')

{{-- Breadcrumb --}}
<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Connexion</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    <h1 class="text-4xl font-bold text-gray-900 text-center mb-12">Mon compte</h1>

    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

            {{-- Login Section --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Connexion</h2>

                @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-6">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Adresse email"
                            class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-4">
                        <input
                            type="password"
                            name="password"
                            placeholder="Mot de passe"
                            class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500"
                            required
                        >
                    </div>

                    <div class="mb-6">
                        <a href="#" class="text-sm text-gray-700 hover:text-gray-900 hover:underline">
                            Mot de passe oublié?
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="w-full bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold py-4 rounded transition-colors mb-6"
                    >
                        Soumettre
                    </button>

                    <div class="flex items-center mb-4">
                        <input
                            type="checkbox"
                            name="remember"
                            id="remember"
                            class="w-4 h-4 text-gray-900 focus:ring-gray-900 rounded"
                        >
                        <label for="remember" class="ml-2 text-sm text-gray-700">
                            Rester connecté
                        </label>
                    </div>

                    <div class="space-y-3">
                        <a
                            href="{{ route('social.redirect', 'facebook') }}"
                            class="w-full border-2 border-gray-300 text-gray-700 font-medium py-3 rounded hover:border-gray-900 transition-colors flex items-center justify-center gap-2"
                        >
                            <i class="fab fa-facebook text-blue-600"></i>
                            Continuer avec Facebook
                        </a>

                        <a
                            href="{{ route('social.redirect', 'google') }}"
                            class="w-full border-2 border-gray-300 text-gray-700 font-medium py-3 rounded hover:border-gray-900 transition-colors flex items-center justify-center gap-2"
                        >
                            <i class="fab fa-google text-red-600"></i>
                            Continuer avec Google
                        </a>
                    </div>
                </form>
            </div>

            {{-- New Customer Section --}}
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Nouveau client</h2>

                <p class="text-gray-700 leading-relaxed mb-8">
                    En créant un compte dans notre boutique, vous pourrez passer par le processus de paiement plus rapidement, stocker plusieurs adresses de livraison, voir et suivre vos commandes dans votre compte et bien plus encore.
                </p>

                <a
                    href="{{ route('register') }}"
                    class="inline-block bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold px-8 py-3 rounded transition-colors"
                >
                    S'inscrire
                </a>
            </div>

        </div>
    </div>

</div>

@endsection
