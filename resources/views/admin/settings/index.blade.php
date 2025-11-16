@extends('layouts.admin')

@section('title', 'Paramètres')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Paramètres du Site</h1>
        <p class="text-gray-600 mt-2">Gérez les paramètres généraux de votre boutique</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Menu -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-4">
                <h2 class="font-semibold mb-4">Catégories</h2>
                <nav class="space-y-2">
                    <a href="#general" class="block px-4 py-2 rounded hover:bg-gray-100 text-[#5a7c6f]">
                        <i class="fas fa-cog mr-2"></i>Général
                    </a>
                    <a href="#store" class="block px-4 py-2 rounded hover:bg-gray-100">
                        <i class="fas fa-store mr-2"></i>Boutique
                    </a>
                    <a href="#email" class="block px-4 py-2 rounded hover:bg-gray-100">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </a>
                    <a href="#payment" class="block px-4 py-2 rounded hover:bg-gray-100">
                        <i class="fas fa-credit-card mr-2"></i>Paiement
                    </a>
                    <a href="#shipping" class="block px-4 py-2 rounded hover:bg-gray-100">
                        <i class="fas fa-shipping-fast mr-2"></i>Livraison
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- General Settings -->
                <div id="general" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Paramètres Généraux</h2>

                    <div class="space-y-4">
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Logo du Site
                            </label>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    @if(getSetting('site_logo'))
                                        <img id="logo-preview" src="{{ asset(getSetting('site_logo')) }}" alt="Logo" class="w-32 h-32 object-contain border-2 border-gray-200 rounded-lg p-2">
                                    @else
                                        <div id="logo-preview" class="w-32 h-32 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="site_logo" id="site_logo" accept="image/*" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-[#5a7c6f] file:text-white
                                        hover:file:bg-[#4a6c5f]
                                        file:cursor-pointer cursor-pointer"
                                        onchange="previewLogo(event)">
                                    <p class="mt-2 text-xs text-gray-500">Recommandé: 200x60px (ratio 10:3), PNG ou JPG, max 2MB</p>
                                    <p class="mt-1 text-xs text-gray-500">Le logo sera automatiquement redimensionné aux dimensions professionnelles</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du Site
                            </label>
                            <input type="text" name="site_name" value="{{ getSetting('site_name', 'Glowing Cosmetics') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Description du Site
                            </label>
                            <textarea name="site_description" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">{{ getSetting('site_description', 'Produits cosmétiques naturels et biologiques') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email de Contact
                            </label>
                            <input type="email" name="contact_email" value="{{ getSetting('contact_email', 'contact@glowing-cosmetics.com') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Téléphone
                            </label>
                            <input type="text" name="phone" value="{{ getSetting('phone', '+33 1 23 45 67 89') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Store Settings -->
                <div id="store" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Paramètres Boutique</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Devise
                            </label>
                            <select name="currency"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                                <option value="FCFA" {{ getSetting('currency', 'FCFA') == 'FCFA' ? 'selected' : '' }}>FCFA</option>
                                <option value="EUR" {{ getSetting('currency', 'FCFA') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="USD" {{ getSetting('currency', 'FCFA') == 'USD' ? 'selected' : '' }}>USD</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                TVA (%)
                            </label>
                            <input type="number" name="tax_rate" value="{{ getSetting('tax_rate', '15') }}" step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Minimum d'Alerte
                            </label>
                            <input type="number" name="low_stock_threshold" value="{{ getSetting('low_stock_threshold', '10') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="enable_reviews" value="1" {{ getSetting('enable_reviews', '1') == '1' ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Activer les avis clients</span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="enable_wishlist" value="1" {{ getSetting('enable_wishlist', '1') == '1' ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Activer la liste de souhaits</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div id="email" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Paramètres Email</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email d'Expédition
                            </label>
                            <input type="email" name="from_email" value="{{ getSetting('from_email', 'noreply@glowing-cosmetics.com') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nom d'Expédition
                            </label>
                            <input type="text" name="from_name" value="{{ getSetting('from_name', 'Glowing Cosmetics') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="send_order_confirmation" value="1" {{ getSetting('send_order_confirmation', '1') == '1' ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Envoyer email de confirmation de commande</span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="send_shipping_notification" value="1" {{ getSetting('send_shipping_notification', '1') == '1' ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Envoyer notification d'expédition</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div id="payment" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Modes de Paiement</h2>
                    <p class="text-sm text-gray-600 mb-4">Activez ou désactivez les méthodes de paiement disponibles pour vos clients</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-credit-card text-2xl text-gray-600 mr-3"></i>
                                <div>
                                    <span class="block text-sm font-medium text-gray-700">Carte de crédit</span>
                                    <span class="text-xs text-gray-500">Visa, Mastercard, Amex</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="payment_credit_card_enabled" value="1"
                                       {{ getSetting('payment_credit_card_enabled', '1') == '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#5a7c6f]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5a7c6f]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fab fa-paypal text-2xl text-blue-600 mr-3"></i>
                                <div>
                                    <span class="block text-sm font-medium text-gray-700">PayPal</span>
                                    <span class="text-xs text-gray-500">Paiement sécurisé via PayPal</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="payment_paypal_enabled" value="1"
                                       {{ getSetting('payment_paypal_enabled', '1') == '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#5a7c6f]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5a7c6f]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-university text-2xl text-gray-600 mr-3"></i>
                                <div>
                                    <span class="block text-sm font-medium text-gray-700">Virement bancaire</span>
                                    <span class="text-xs text-gray-500">Transfert bancaire direct</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="payment_bank_transfer_enabled" value="1"
                                       {{ getSetting('payment_bank_transfer_enabled', '1') == '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#5a7c6f]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5a7c6f]"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-truck text-2xl text-green-600 mr-3"></i>
                                <div>
                                    <span class="block text-sm font-medium text-gray-700">Paiement à la livraison</span>
                                    <span class="text-xs text-gray-500">Payez en espèces lors de la livraison</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="payment_cash_on_delivery_enabled" value="1"
                                       {{ getSetting('payment_cash_on_delivery_enabled', '1') == '1' ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#5a7c6f]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5a7c6f]"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Shipping Settings -->
                <div id="shipping" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Paramètres Livraison</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Frais de Livraison Standard
                            </label>
                            <input type="number" name="standard_shipping_cost" value="{{ getSetting('standard_shipping_cost', '0') }}" step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Livraison Gratuite à partir de
                            </label>
                            <input type="number" name="free_shipping_threshold" value="{{ getSetting('free_shipping_threshold', '50000') }}" step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Délai de Livraison Estimé (jours)
                            </label>
                            <input type="text" name="estimated_delivery_days" value="{{ getSetting('estimated_delivery_days', '3-5') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-3 bg-[#5a7c6f] text-white rounded-lg hover:bg-[#4a6c5f] transition">
                        <i class="fas fa-save mr-2"></i>Enregistrer les Paramètres
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewLogo(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="w-32 h-32 object-contain border-2 border-gray-200 rounded-lg p-2">`;
        }
        reader.readAsDataURL(file);
    }
}
</script>
@endsection
