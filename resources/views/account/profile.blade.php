@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Mon profil</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <a href="{{ route('account.dashboard') }}" class="text-gray-600 hover:text-gray-900">Mon compte</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Profil</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        {{-- Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-200">
                    @if($user->avatar)
                        <img src="{{ asset($user->avatar) }}" alt="{{ $user->first_name }}"
                             class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600 text-xl"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</h3>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-home w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('account.orders') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-shopping-bag w-5"></i>
                        <span>Mes commandes</span>
                    </a>
                    <a href="{{ route('account.profile') }}" class="flex items-center gap-3 px-4 py-3 bg-gray-100 text-gray-900 rounded-lg font-medium">
                        <i class="fas fa-user-edit w-5"></i>
                        <span>Mon profil</span>
                    </a>
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-3 px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <i class="fas fa-heart w-5"></i>
                        <span>Ma liste de souhaits</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-sign-out-alt w-5"></i>
                            <span>Déconnexion</span>
                        </button>
                    </form>
                </nav>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="lg:col-span-3">
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif

            <form action="{{ route('account.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Avatar Upload --}}
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Photo de Profil</h2>

                    <div class="flex items-center gap-6">
                        @if($user->avatar)
                            <img src="{{ asset($user->avatar) }}" alt="{{ $user->first_name }}" id="avatar-preview"
                                 class="w-24 h-24 rounded-full object-cover">
                        @else
                            <div id="avatar-preview" class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-400 text-3xl"></i>
                            </div>
                        @endif

                        <div class="flex-1">
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden" onchange="previewAvatar(event)">
                            <label for="avatar" class="inline-block px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 cursor-pointer transition">
                                <i class="fas fa-camera mr-2"></i>Changer la photo
                            </label>
                            <p class="text-sm text-gray-600 mt-2">JPG, JPEG ou PNG. Max 2MB.</p>
                            @error('avatar')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Personal Information --}}
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations personnelles</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Prénom *</label>
                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name', $user->first_name) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('first_name') border-red-500 @enderror"
                                required
                            >
                            @error('first_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Nom *</label>
                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name', $user->last_name) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('last_name') border-red-500 @enderror"
                                required
                            >
                            @error('last_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Email *</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('email') border-red-500 @enderror"
                                required
                            >
                            @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Téléphone</label>
                            <input
                                type="tel"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('phone') border-red-500 @enderror"
                            >
                            @error('phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Address Information --}}
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Adresse</h2>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Adresse</label>
                            <input
                                type="text"
                                name="address"
                                value="{{ old('address', $user->address) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('address') border-red-500 @enderror"
                            >
                            @error('address')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Ville</label>
                                <input
                                    type="text"
                                    name="city"
                                    value="{{ old('city', $user->city) }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('city') border-red-500 @enderror"
                                >
                                @error('city')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Code postal</label>
                                <input
                                    type="text"
                                    name="postal_code"
                                    value="{{ old('postal_code', $user->postal_code) }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('postal_code') border-red-500 @enderror"
                                >
                                @error('postal_code')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Pays</label>
                                <select
                                    name="country"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('country') border-red-500 @enderror"
                                >
                                    <option value="">Sélectionner</option>
                                    <option value="Canada" {{ old('country', $user->country) == 'Canada' ? 'selected' : '' }}>Canada</option>
                                    <option value="France" {{ old('country', $user->country) == 'France' ? 'selected' : '' }}>France</option>
                                    <option value="Belgium" {{ old('country', $user->country) == 'Belgium' ? 'selected' : '' }}>Belgique</option>
                                    <option value="Switzerland" {{ old('country', $user->country) == 'Switzerland' ? 'selected' : '' }}>Suisse</option>
                                    <option value="USA" {{ old('country', $user->country) == 'USA' ? 'selected' : '' }}>États-Unis</option>
                                </select>
                                @error('country')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Change Password --}}
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Changer le mot de passe</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Mot de passe actuel</label>
                            <input
                                type="password"
                                name="current_password"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('current_password') border-red-500 @enderror"
                            >
                            @error('current_password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">Laissez vide si vous ne souhaitez pas changer votre mot de passe</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Nouveau mot de passe</label>
                                <input
                                    type="password"
                                    name="new_password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f] @error('new_password') border-red-500 @enderror"
                                >
                                @error('new_password')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Confirmer le nouveau mot de passe</label>
                                <input
                                    type="password"
                                    name="new_password_confirmation"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5a7c6f]"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex gap-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                    </button>
                    <a href="{{ route('account.dashboard') }}" class="btn btn-outline">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                preview.outerHTML = '<img src="' + e.target.result + '" alt="Preview" id="avatar-preview" class="w-24 h-24 rounded-full object-cover">';
            }
        }
        reader.readAsDataURL(file);
    }
}
</script>
@endpush
