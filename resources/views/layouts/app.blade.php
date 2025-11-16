<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Glowing Cosmetics'))</title>

    <link rel="icon" href="{{ asset('images/others/favicon.ico') }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Slick Carousel CSS --}}
    <link rel="stylesheet" href="{{ asset('vendors/slick/slick.css') }}">

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])</script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white">

    @include('layouts.partials.header')

    <main id="content">
        @yield('content')
    </main>

    @include('layouts.partials.footer')

    {{-- jQuery --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    {{-- Slick Carousel JS --}}
    <script src="{{ asset('vendors/slick/slick.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
