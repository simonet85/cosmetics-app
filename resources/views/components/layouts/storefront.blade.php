@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'robots' => null,
])

@php
    $brand = config('app.name', 'Glowing Cosmetics');
    $pageTitle = $title ? "{$title} | {$brand}" : $brand;
    $canonicalUrl = $canonical ?? url()->current();
    $shareImage = $image ?? asset('images/others/logo-black-02.png');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b7a4b">

    <title>{{ $pageTitle }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ asset('images/others/favicon.ico') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:title" content="{{ $pageTitle }}">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">

    {{-- Lets the CSS hide [data-reveal] content only when scripts can reveal it again --}}
    <script>document.documentElement.classList.add('js')</script>

    @vite(['resources/css/storefront.css', 'resources/js/storefront.js'])

    {{ $head ?? '' }}
</head>
<body class="min-h-dvh bg-ground font-sans text-ink antialiased">
    <a href="#content" class="skip-link">Aller au contenu</a>

    <x-storefront.header />

    <main id="content" tabindex="-1" class="outline-none">
        {{ $slot }}
    </main>

    <x-storefront.footer />

    {{-- Polite announcements (cart additions, errors): they never take focus --}}
    <div id="toast-region" class="pointer-events-none fixed inset-x-4 bottom-4 z-40 grid gap-2 sm:left-auto sm:w-96" role="status" aria-live="polite"></div>

    {{ $scripts ?? '' }}
</body>
</html>
