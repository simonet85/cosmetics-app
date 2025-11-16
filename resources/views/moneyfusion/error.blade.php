@extends('layouts.app')

@section('title', 'Erreur de paiement')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto">
        <div class="bg-red-50 border-l-4 border-red-400 p-8 rounded-lg shadow-lg text-center">
            <div class="mb-6">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500"></i>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Erreur de paiement</h1>

            <p class="text-gray-700 mb-6">
                {{ $message ?? 'Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.' }}
            </p>

            <div class="space-x-4">
                <a href="{{ route('home') }}" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition">
                    Retour à l'accueil
                </a>
                <a href="{{ route('checkout.index') }}" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg border-2 border-gray-900 hover:bg-gray-50 transition">
                    Retour au paiement
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
