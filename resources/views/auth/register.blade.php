@extends('layouts.app')

@section('content')

{{-- Breadcrumb --}}
<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Inscription</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    <h1 class="text-4xl font-bold text-gray-900 text-center mb-12">S'inscrire</h1>

    <div class="max-w-md mx-auto">

        @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}">
            @csrf

            <div class="mb-6">
                <input
                    type="text"
                    name="first_name"
                    value="{{ old('first_name') }}"
                    placeholder="Prénom"
                    class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500 @error('first_name') ring-2 ring-red-500 @enderror"
                    required
                    autofocus
                >
                @error('first_name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <input
                    type="text"
                    name="last_name"
                    value="{{ old('last_name') }}"
                    placeholder="Nom"
                    class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500 @error('last_name') ring-2 ring-red-500 @enderror"
                    required
                >
                @error('last_name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Votre email"
                    class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500 @error('email') ring-2 ring-red-500 @enderror"
                    required
                >
                @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <input
                    type="password"
                    name="password"
                    placeholder="Mot de passe"
                    class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500 @error('password') ring-2 ring-red-500 @enderror"
                    required
                >
                @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <input
                    type="password"
                    name="password_confirmation"
                    placeholder="Confirmer le mot de passe"
                    class="w-full px-4 py-4 bg-gray-50 border-0 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 placeholder-gray-500"
                    required
                >
            </div>

            <div class="flex items-start mb-6">
                <input
                    type="checkbox"
                    name="terms"
                    id="terms"
                    class="w-4 h-4 text-gray-900 focus:ring-gray-900 rounded mt-1"
                    required
                >
                <label for="terms" class="ml-2 text-sm text-gray-700">
                    Oui, j'accepte la
                    <a href="#" class="text-gray-900 underline hover:no-underline">Politique de confidentialité</a>
                    et les
                    <a href="#" class="text-gray-900 underline hover:no-underline">Conditions d'utilisation</a>
                </label>
            </div>

            <button
                type="submit"
                class="w-full bg-[#5a7c6f] hover:bg-[#4a6c5f] text-white font-semibold py-4 rounded transition-colors mb-8"
            >
                S'inscrire
            </button>

            <div class="text-center mb-4">
                <span class="text-gray-600">ou s'inscrire avec</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a
                    href="{{ route('social.redirect', 'facebook') }}"
                    class="border-2 border-gray-300 text-gray-700 font-medium py-3 rounded hover:border-gray-900 transition-colors flex items-center justify-center gap-2"
                >
                    <i class="fab fa-facebook text-blue-600"></i>
                    Facebook
                </a>

                <a
                    href="{{ route('social.redirect', 'google') }}"
                    class="border-2 border-gray-300 text-gray-700 font-medium py-3 rounded hover:border-gray-900 transition-colors flex items-center justify-center gap-2"
                >
                    <i class="fab fa-google text-red-600"></i>
                    Google
                </a>
            </div>

            <div class="text-center mt-6">
                <p class="text-gray-700">
                    Vous avez déjà un compte?
                    <a href="{{ route('login') }}" class="text-gray-900 font-semibold hover:underline">Se connecter</a>
                </p>
            </div>
        </form>

    </div>

</div>

@endsection
