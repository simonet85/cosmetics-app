@extends('layouts.app')

@section('title', 'Paiement en attente')

@section('content')
<div class="container mx-auto px-4 py-16">
    <div class="max-w-2xl mx-auto">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-8 rounded-lg shadow-lg text-center">
            <div class="mb-6">
                <i class="fas fa-clock text-6xl text-yellow-500"></i>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">Paiement en attente</h1>

            <p class="text-gray-700 mb-6">
                Votre paiement est en cours de traitement. Vous recevrez une confirmation une fois le paiement validé.
            </p>

            @if($order)
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Détails de la commande</h2>
                <div class="text-left space-y-2">
                    <p><span class="font-semibold">Numéro de commande:</span> {{ $order->order_number }}</p>
                    <p><span class="font-semibold">Montant:</span> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
                    <p><span class="font-semibold">Email:</span> {{ $order->customer_email }}</p>
                </div>
            </div>
            @endif

            <div class="space-x-4">
                <a href="{{ route('home') }}" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition">
                    Retour à l'accueil
                </a>
                @if($order)
                <a href="{{ route('checkout.success', $order->id) }}" class="inline-block bg-white text-gray-900 px-8 py-3 rounded-lg border-2 border-gray-900 hover:bg-gray-50 transition">
                    Voir ma commande
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
