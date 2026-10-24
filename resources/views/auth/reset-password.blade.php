@extends('layouts.app')

@section('content')

<div class="bg-gray-50 py-4 mb-12">
    <div class="container-custom">
        <nav class="flex items-center text-sm">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Nouveau mot de passe</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Nouveau mot de passe</h1>

        @if ($errors->any())
            <div role="alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email" class="block font-semibold text-gray-900 mb-2">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"
                   class="w-full px-4 py-4 bg-gray-50 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 mb-6">

            <label for="password" class="block font-semibold text-gray-900 mb-2">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" aria-describedby="password-help"
                   class="w-full px-4 py-4 bg-gray-50 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 mb-1">
            <p id="password-help" class="text-sm text-gray-600 mb-6">8 caractères minimum.</p>

            <label for="password_confirmation" class="block font-semibold text-gray-900 mb-2">Confirmez le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"
                   class="w-full px-4 py-4 bg-gray-50 border border-gray-400 rounded focus:outline-none focus:ring-2 focus:ring-gray-900 text-gray-900 mb-6">

            <button type="submit" class="btn btn-primary w-full">Enregistrer le mot de passe</button>
        </form>
    </div>
</div>
@endsection
