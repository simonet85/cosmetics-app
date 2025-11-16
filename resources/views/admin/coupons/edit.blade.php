@extends('layouts.admin')

@section('title', 'Modifier le Coupon')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('admin.coupons.index') }}" class="text-[#5a7c6f] hover:text-[#4a6c5f] mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux coupons
        </a>
        <h1 class="text-3xl font-bold">Modifier le Coupon</h1>
    </div>

    <form action="{{ route('admin.coupons.update', $coupon->id) }}" method="POST" class="bg-white rounded-lg shadow-md p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Code -->
            <div class="md:col-span-2">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                    Code du Coupon <span class="text-red-500">*</span>
                </label>
                <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent uppercase"
                       required>
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                    Type de Réduction <span class="text-red-500">*</span>
                </label>
                <select name="type" id="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                        required onchange="toggleMaxDiscount()">
                    <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Montant Fixe</option>
                    <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Pourcentage</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Value -->
            <div>
                <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                    Valeur <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="value" id="value" value="{{ old('value', $coupon->value) }}"
                           step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent"
                           required>
                    <span id="value-unit" class="absolute right-3 top-2.5 text-gray-500">
                        {{ $coupon->type === 'percentage' ? '%' : 'FCFA' }}
                    </span>
                </div>
                @error('value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Min Amount -->
            <div>
                <label for="min_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Montant Minimum
                </label>
                <input type="number" name="min_amount" id="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}"
                       step="0.01" min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('min_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Montant minimum requis pour utiliser le coupon</p>
            </div>

            <!-- Max Discount -->
            <div id="max-discount-field" style="display: {{ old('type', $coupon->type) === 'percentage' ? 'block' : 'none' }}">
                <label for="max_discount" class="block text-sm font-medium text-gray-700 mb-2">
                    Réduction Maximum
                </label>
                <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}"
                       step="0.01" min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('max_discount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Montant maximum de réduction (pour les pourcentages)</p>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Date de Début
                </label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ old('start_date', $coupon->start_date ? $coupon->start_date->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('start_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Date de Fin
                </label>
                <input type="date" name="end_date" id="end_date"
                       value="{{ old('end_date', $coupon->end_date ? $coupon->end_date->format('Y-m-d') : '') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('end_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Usage Limit -->
            <div>
                <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-2">
                    Limite d'Utilisation
                </label>
                <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}"
                       min="0"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#5a7c6f] focus:border-transparent">
                @error('usage_limit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Laisser vide pour illimité</p>
            </div>

            <!-- Statistics -->
            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-2">Statistiques</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Utilisations:</span>
                        <span class="font-semibold ml-2">{{ $coupon->used_count }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Créé le:</span>
                        <span class="font-semibold ml-2">{{ $coupon->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Modifié le:</span>
                        <span class="font-semibold ml-2">{{ $coupon->updated_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Is Active -->
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-[#5a7c6f] shadow-sm focus:border-[#5a7c6f] focus:ring focus:ring-[#5a7c6f] focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Coupon actif</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.coupons.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuler
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-[#5a7c6f] text-white rounded-lg hover:bg-[#4a6c5f] transition">
                <i class="fas fa-save mr-2"></i>Mettre à Jour
            </button>
        </div>
    </form>
</div>

<script>
function toggleMaxDiscount() {
    const type = document.getElementById('type').value;
    const maxDiscountField = document.getElementById('max-discount-field');
    const valueUnit = document.getElementById('value-unit');

    if (type === 'percentage') {
        maxDiscountField.style.display = 'block';
        valueUnit.textContent = '%';
    } else {
        maxDiscountField.style.display = 'none';
        valueUnit.textContent = 'FCFA';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleMaxDiscount);
</script>
@endsection
