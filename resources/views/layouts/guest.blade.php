<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="guestTheme()" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'DICT R2 Accounts'))</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css" integrity="sha512-QeR2VH+lsBE5LSAe1Q5EnTBbe7XTBubt8dG93Y7gidSgdMCr8nVqKcfKAMyN96SV8KDbZVTDXChatu5G2KQGzg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        html{scroll-behavior:smooth}
        input:-webkit-autofill,input:-webkit-autofill:hover,input:-webkit-autofill:focus,input:-webkit-autofill:active{-webkit-box-shadow:0 0 0 30px rgba(255,255,255,.92) inset!important;-webkit-text-fill-color:#0f172a!important}
        html.dark input:-webkit-autofill,html.dark input:-webkit-autofill:hover,html.dark input:-webkit-autofill:focus,html.dark input:-webkit-autofill:active{-webkit-box-shadow:0 0 0 30px rgba(15,23,42,.92) inset!important;-webkit-text-fill-color:#f8fafc!important}
        @keyframes guest-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
        .guest-logo-float{animation:guest-float 5s ease-in-out infinite}
    </style>
    @stack('head')
</head>

<body class="min-h-screen overflow-x-hidden bg-slate-950 text-slate-900 antialiased">
    <div class="fixed inset-0 -z-20 bg-cover bg-center bg-no-repeat" style="background-image:url('{{ asset('background.png') }}')"></div>
    <div class="fixed inset-0 -z-10 bg-white/25 dark:bg-slate-950/60"></div>
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-white/25 via-transparent to-blue-950/25 dark:from-slate-950/10 dark:via-transparent dark:to-black/40"></div>

    <div class="relative flex min-h-screen flex-col md:flex-row">

        {{-- Desktop left panel --}}
        <aside class="relative hidden min-h-screen w-1/2 overflow-hidden md:flex">
            <div class="absolute inset-0 bg-white/5 dark:bg-black/10"></div>

            <div class="relative z-10 flex w-full flex-col justify-between p-10 lg:p-16 xl:p-20">
                @yield('guest-sidebar')
            </div>
        </aside>

        {{-- Main panel --}}
        <main class="relative min-h-screen w-full md:w-1/2">
            <button
                type="button"
                @click="toggle()"
                class="fixed right-4 top-4 z-50 flex h-10 w-10 items-center justify-center rounded-full border border-white/50 bg-white/25 text-slate-700 shadow-lg backdrop-blur-xl transition hover:bg-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-white/10 dark:bg-slate-900/30 dark:text-slate-200 dark:hover:bg-slate-800/50 sm:right-5 sm:top-5"
                aria-label="Toggle dark mode"
            >
                <i x-show="!dark" class="fa-solid fa-moon"></i>
                <i x-show="dark" x-cloak class="fa-solid fa-sun"></i>
            </button>

            {{-- Mobile branding --}}
            @hasSection('guest-mobile')
                <div class="px-4 pb-0 pt-8 sm:px-0 sm:pt-2 md:hidden">
                    @yield('guest-mobile')
                </div>
            @endif

            {{-- Content --}}
            <div class="flex min-h-screen lg:items-center justify-center px-4 py-3 lg:py-0">
                <div class="w-full max-w-md pt-0">
                    <div class="rounded-[2rem] border border-white/30 bg-white/5 p-10 shadow-[0_25px_80px_-20px_rgba(15,23,42,.35)] backdrop-blur-3xl ring-1 ring-white/20 transition-all duration-500 sm:p-2 lg:p-8 dark:border-white/10 dark:bg-slate-900/35 dark:shadow-[0_25px_80px_-20px_rgba(0,0,0,.55)] dark:ring-white/5">
                            @yield('content')
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function guestTheme(){
            return {
                dark:false,
                init(){
                    const stored=localStorage.getItem('theme');
                    this.dark=stored ? stored==='dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    this.apply();
                },
                toggle(){
                    this.dark=!this.dark;
                    localStorage.setItem('theme',this.dark?'dark':'light');
                    this.apply();
                },
                apply(){
                    document.documentElement.classList.toggle('dark',this.dark);
                }
            }
        }
    </script>

    @stack('scripts')
</body>
</html>