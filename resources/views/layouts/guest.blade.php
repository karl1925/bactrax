<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Procurement System') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-900 text-slate-100 flex items-center justify-center p-4 sm:p-6">

    <div class="w-full max-w-md space-y-6">
        
        <!-- Branding Header -->
        <div class="text-center">
            <a href="/" class="inline-flex items-center justify-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30">
                    <i class="fa-solid fa-boxes-packing text-2xl"></i>
                </div>
            </a>
            <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">
                Procure<span class="text-indigo-400">Hub</span>
            </h2>
            <p class="mt-1 text-xs text-slate-400">
                Procurement Management System & Routing Portal
            </p>
        </div>

        <!-- Card Container -->
        <div class="bg-slate-800/80 border border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-sm">
            
            <!-- Global Flash Messages -->
            @if (session('status'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-sm"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
            
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Procurement Management System.
        </p>

    </div>

</body>
</html>