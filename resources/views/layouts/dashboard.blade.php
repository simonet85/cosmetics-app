<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/others/favicon.ico') }}">

    {{-- Vendors CSS --}}
    <link rel="stylesheet" href="{{ asset('vendors/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Theme CSS --}}
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}">

    @stack('styles')
</head>
<body class="dashboard-layout">

    @include('layouts.partials.dashboard-header')

    <div class="d-flex">
        @include('layouts.partials.dashboard-sidebar')

        <main class="flex-grow-1 p-4" style="margin-left: 250px;">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Vendors JS --}}
    <script src="{{ asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.bundle.min.js') }}"></script>

    {{-- Dashboard JS --}}
    <script src="{{ asset('js/dashboard.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
