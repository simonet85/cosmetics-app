@extends('layouts.admin')

@section('title', 'Détails de la Commande #' . $order->order_number)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-green hover:text-green-deep mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Retour aux commandes
            </a>
            <h1 class="text-3xl font-bold">Commande #{{ $order->order_number }}</h1>
            <p class="text-gray-600 mt-2">Passée le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        </div>
        <a href="{{ route('admin.orders.invoice', $order) }}"
           class="bg-white border border-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-file-pdf mr-2" aria-hidden="true"></i>Télécharger la facture
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Status Management -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Gestion du Statut</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Order Status -->
                    <div>
                        <label for="order-status" class="block text-sm font-medium text-gray-700 mb-2">Statut de la commande</label>
                        <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            @method('PUT')
                            <select id="order-status" name="status" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                                @foreach (\App\Support\OrderLabels::statuses() as $value => $label)
                                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                                <i class="fas fa-save" aria-hidden="true"></i><span class="sr-only">Enregistrer le statut</span>
                            </button>
                        </form>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label for="payment-status" class="block text-sm font-medium text-gray-700 mb-2">Statut du paiement</label>
                        <form action="{{ route('admin.orders.update-payment-status', $order->id) }}" method="POST" class="flex gap-2">
                            @csrf
                            @method('PUT')
                            <select id="payment-status" name="payment_status" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                                @foreach (\App\Support\OrderLabels::payments() as $value => $label)
                                    <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                                <i class="fas fa-save" aria-hidden="true"></i><span class="sr-only">Enregistrer le statut du paiement</span>
                            </button>
                        </form>
                        <p class="text-xs text-gray-500 mt-1">Marquer « Payé » envoie la confirmation et la facture au client, comme un paiement en ligne.</p>
                    </div>
                </div>
            </div>

            <!-- Shipping Tracking -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Suivi de livraison</h2>

                <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $order->status }}">

                    <div>
                        <label for="shipping-carrier" class="block text-sm font-medium text-gray-700 mb-2">Transporteur</label>
                        <input type="text" id="shipping-carrier" name="shipping_carrier" maxlength="255"
                               value="{{ old('shipping_carrier', $order->shipping_carrier) }}"
                               placeholder="Ex. DHL, Yalidine..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    </div>

                    <div>
                        <label for="tracking-number" class="block text-sm font-medium text-gray-700 mb-2">N° de suivi</label>
                        <input type="text" id="tracking-number" name="tracking_number" maxlength="255"
                               value="{{ old('tracking_number', $order->tracking_number) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    </div>

                    <div>
                        <label for="tracking-url" class="block text-sm font-medium text-gray-700 mb-2">Lien de suivi</label>
                        <input type="url" id="tracking-url" name="tracking_url" maxlength="500"
                               value="{{ old('tracking_url', $order->tracking_url) }}"
                               placeholder="https://..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green focus:border-transparent">
                    </div>

                    <div class="md:col-span-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-gray-500">Laissez un champ vide pour l'effacer.</p>
                        <button type="submit" class="bg-green text-white px-4 py-2 rounded-lg hover:bg-green-deep transition">
                            <i class="fas fa-truck mr-2" aria-hidden="true"></i>Enregistrer le suivi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Order Items -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Produits Commandés</h2>

                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex gap-4 pb-4 border-b last:border-b-0">
                            @if($item->product && $item->product->primaryImage)
                                <img src="{{ asset($item->product->primaryImage->path) }}"
                                     alt="{{ $item->product_name }}"
                                     class="w-20 h-20 object-cover rounded">
                            @else
                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400" aria-hidden="true"></i>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h3 class="font-semibold">{{ $item->product_name }}</h3>
                                <p class="text-sm text-gray-600">SKU: {{ $item->sku }}</p>
                                <p class="text-sm text-gray-600">Quantité: {{ $item->quantity }}</p>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold">{{ format_fcfa($item->total) }}</p>
                                <p class="text-sm text-gray-600">{{ format_fcfa($item->price) }} × {{ $item->quantity }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Informations Client</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Contact</h3>
                        @if($order->user)
                            <p class="text-gray-900">{{ $order->user->first_name }} {{ $order->user->last_name }}</p>
                        @endif
                        <p class="text-gray-600">{{ $order->customer_email }}</p>
                        @if($order->customer_phone)
                            <p class="text-gray-600">{{ $order->customer_phone }}</p>
                        @endif
                    </div>

                    <div>
                        <h3 class="font-semibold text-gray-700 mb-2">Adresse de Livraison</h3>
                        @php
                            $shipping = $order->shipping_address;
                        @endphp
                        @if($shipping)
                            <p class="text-gray-900">{{ $shipping['first_name'] ?? '' }} {{ $shipping['last_name'] ?? '' }}</p>
                            <p class="text-gray-600">{{ $shipping['address'] ?? '' }}</p>
                            @if(isset($shipping['address_line_2']) && $shipping['address_line_2'])
                                <p class="text-gray-600">{{ $shipping['address_line_2'] }}</p>
                            @endif
                            <p class="text-gray-600">
                                {{ $shipping['city'] ?? '' }}, {{ $shipping['state'] ?? '' }} {{ $shipping['zip_code'] ?? '' }}
                            </p>
                            <p class="text-gray-600">{{ $shipping['country'] ?? '' }}</p>
                        @endif
                    </div>
                </div>

                @if($order->notes)
                    <div class="mt-4 pt-4 border-t">
                        <h3 class="font-semibold text-gray-700 mb-2">Notes</h3>
                        <p class="text-gray-600">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Résumé</h2>

                <div class="space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Sous-total</span>
                        <span>{{ format_fcfa($order->subtotal) }}</span>
                    </div>

                    <div class="flex justify-between text-gray-600">
                        <span>Livraison</span>
                        <span>{{ format_fcfa($order->shipping_cost) }}</span>
                    </div>

                    <div class="flex justify-between text-gray-600">
                        <span>TVA</span>
                        <span>{{ format_fcfa($order->tax) }}</span>
                    </div>

                    @if($order->discount > 0)
                        <div class="flex justify-between text-green-600">
                            <span>Réduction</span>
                            <span>-{{ format_fcfa($order->discount) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-xl font-bold pt-2 border-t">
                        <span>Total</span>
                        <span>{{ format_fcfa($order->total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Paiement</h2>

                <div class="space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Méthode</span>
                        <span class="font-medium text-right">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600">Montant</span>
                        <span class="font-medium">{{ format_fcfa($order->total) }}</span>
                    </div>

                    <div class="flex justify-between gap-4 items-center">
                        <span class="text-gray-600">Statut</span>
                        @include('admin.orders.partials.payment-badge', ['status' => $order->payment_status])
                    </div>

                    @if($order->payment_method === 'moneyfusion')
                        @php $mfPayment = $order->moneyFusionPayment; @endphp
                        @if($mfPayment)
                            <hr class="my-2">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Moyen</span>
                                <span class="font-medium text-right">{{ $mfPayment->moyen ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">N° de transaction</span>
                                <span class="font-mono text-xs break-all text-right ml-4">{{ $mfPayment->numero_transaction ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Numéro envoyé</span>
                                <span class="font-medium text-right">{{ $mfPayment->numero_send ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Montant crédité (net de frais)</span>
                                <span class="font-medium text-right">{{ format_fcfa($mfPayment->montant) }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Frais MoneyFusion</span>
                                <span class="font-medium text-right">{{ format_fcfa($mfPayment->frais) }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Jeton MoneyFusion</span>
                                <span class="font-mono text-xs break-all text-right ml-4">{{ $mfPayment->token_pay }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Paiement créé le</span>
                                <span class="font-medium text-right">{{ $mfPayment->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Statut chez MoneyFusion</span>
                                <span class="font-medium text-right">{{ $mfPayment->statut }}</span>
                            </div>
                            @if($mfPayment->paid_at)
                                <div class="flex justify-between gap-4">
                                    <span class="text-gray-600">Payé le</span>
                                    <span class="font-medium text-right">{{ $mfPayment->paid_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        @endif

                        @if($paymentUrl = $order->moneyfusion_payment_url ?: $order->moneyFusionPayment?->payment_url)
                            <div class="pt-2">
                                <a href="{{ $paymentUrl }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 text-sm text-green hover:text-green-deep">
                                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>Ouvrir le lien de paiement
                                </a>
                            </div>
                        @endif

                        @if($order->payment_status !== 'paid')
                            <form action="{{ route('admin.orders.check-payment', $order) }}" method="POST" class="pt-3">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green text-white rounded-lg hover:bg-green-deep transition-colors">
                                    <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>Vérifier le paiement
                                </button>
                                <p class="text-xs text-gray-500 mt-1">Interroge MoneyFusion et met la commande à jour.</p>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Order Status -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Statut</h2>
                @include('admin.orders.partials.status-badge', ['status' => $order->status, 'size' => 'px-4 py-2 text-sm leading-5'])
            </div>
        </div>
    </div>
</div>
@endsection
