@extends('layouts.app')

@section('content')

{{-- Page Header --}}
<div class="bg-gray-50 py-12 mb-12">
    <div class="container-custom">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">Contactez-nous</h1>
        <nav class="flex text-sm">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-gray-900">Accueil</a>
            <span class="mx-2 text-gray-400">/</span>
            <span class="text-gray-900">Contact</span>
        </nav>
    </div>
</div>

<div class="container-custom mb-20">

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-8">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

        {{-- Contact Form --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Envoyez-nous un message</h2>
            <p class="text-gray-700 mb-8">
                Vous avez une question, une suggestion ou besoin d'aide? N'hésitez pas à nous contacter. Notre équipe vous répondra dans les plus brefs délais.
            </p>

            <form action="{{ route('pages.contact.submit') }}" method="POST">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Nom complet *</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('name') border-red-500 @enderror"
                        required
                    >
                    @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Adresse email *</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('email') border-red-500 @enderror"
                        required
                    >
                    @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Téléphone</label>
                    <input
                        type="tel"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('phone') border-red-500 @enderror"
                    >
                    @error('phone')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Sujet *</label>
                    <input
                        type="text"
                        name="subject"
                        value="{{ old('subject') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('subject') border-red-500 @enderror"
                        required
                    >
                    @error('subject')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-900 mb-2">Message *</label>
                    <textarea
                        name="message"
                        rows="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 @error('message') border-red-500 @enderror"
                        required
                    >{{ old('message') }}</textarea>
                    @error('message')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full md:w-auto px-8">
                    <i class="fas fa-paper-plane mr-2"></i>Envoyer le message
                </button>
            </form>
        </div>

        {{-- Contact Information --}}
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations de contact</h2>

            <div class="space-y-6 mb-12">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#5a7c6f] rounded-full flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-1">Adresse</h3>
                        <p class="text-gray-700">
                            123 Rue de la Beauté<br>
                            Montréal, QC H2X 1Y4<br>
                            Canada
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#5a7c6f] rounded-full flex items-center justify-center">
                        <i class="fas fa-phone text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-1">Téléphone</h3>
                        <p class="text-gray-700">
                            +1 (514) 123-4567<br>
                            Lun - Ven: 9h00 - 18h00
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#5a7c6f] rounded-full flex items-center justify-center">
                        <i class="fas fa-envelope text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-1">Email</h3>
                        <p class="text-gray-700">
                            contact@glowing.com<br>
                            support@glowing.com
                        </p>
                    </div>
                </div>
            </div>

            {{-- Business Hours --}}
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Heures d'ouverture</h3>
                <div class="space-y-2 text-gray-700">
                    <div class="flex justify-between">
                        <span>Lundi - Vendredi:</span>
                        <span class="font-semibold">9h00 - 18h00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Samedi:</span>
                        <span class="font-semibold">10h00 - 17h00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Dimanche:</span>
                        <span class="font-semibold">Fermé</span>
                    </div>
                </div>
            </div>

            {{-- Social Media --}}
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Suivez-nous</h3>
                <div class="flex gap-3">
                    <a href="#" class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white hover:bg-gray-700 transition">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white hover:bg-gray-700 transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white hover:bg-gray-700 transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center text-white hover:bg-gray-700 transition">
                        <i class="fab fa-pinterest"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
