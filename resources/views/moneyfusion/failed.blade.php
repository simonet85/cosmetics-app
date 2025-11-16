@extends('layouts.app')

@section('title', 'Échec du paiement')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto">
        <div class="bg-red-50 border-l-4 border-red-400 p-8 rounded-lg shadow-lg text-center">
            <div class="mb-6">
                <i class="fas fa-times-circle text-6xl text-red-500"></i>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Échec du paiement</h1>

            <p class="text-gray-700 mb-6">
                Votre paiement n'a pas pu être traité. Veuillez réessayer ou choisir un autre moyen de paiement.
            </p>

            @if($order)
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Détails de la commande</h2>
                <div class="text-left space-y-2">
                    <p><span class="font-semibold">Numéro de commande:</span> {{ $order->order_number }}</p>
                    <p><span class="font-semibold">Montant:</span> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                    <p><span class="font-semibold">Email:</span> {{ $order->customer_email }}</p>
                    <p><span class="font-semibold">Statut:</span>
                        <span class="inline-block px-3 py-1 text-sm bg-red-100 text-red-800 rounded">Échoué</span>
                    </p>
                </div>
            </div>
            @endif

            <div class="space-x-4">
                <a href="{{ route('home') }}" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition">
                    Retour à l'accueil
                </a>
                @if($order)
                <a href="{{ route('checkout.index') }}" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg border-2 border-gray-900 hover:bg-gray-50 transition">
                    Réessayer le paiement
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
