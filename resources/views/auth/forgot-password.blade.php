@extends('layouts.app')

@section('content')

<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex items-center text-sm">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-gray-600 hover:text-gray-900">Connexion</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Mot de passe oublié</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Mot de passe oublié</h1>
        <p class="text-gray-700 mb-8">Indiquez l'adresse e-mail de votre compte. Nous vous envoyons un lien pour choisir un nouveau mot de passe.</p>

        @if (session('status'))
            <div role="status" class="bg-green-50 border border-green-600 text-green-900 px-4 py-3 rounded mb-6">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email" class="block font-semibold text-gray-900 mb-2">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="w-full px-4 py-4 bg-gray-50 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 mb-6">
            <button type="submit" class="btn btn-primary w-full">Envoyer le lien</button>
        </form>

        <p class="mt-6"><a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-gray-900 underline">Retour à la connexion</a></p>
    </div>
</div>
@endsection
